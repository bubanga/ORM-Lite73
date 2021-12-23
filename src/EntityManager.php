<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ORMLite;


use DevLancer\ORMLite\Exception\BadEntityException;
use DevLancer\ORMLite\Exception\NotFoundRepositoryException;
use DevLancer\ORMLite\Query\DeleteBuilder;
use DevLancer\ORMLite\Query\InsertBuilder;
use DevLancer\ORMLite\Query\SelectBuilder;
use DevLancer\ORMLite\Query\UpdateBuilder;
use PDO;

/**
 * Class EntityManager
 * @package DevLancer\ORMLite
 */
class EntityManager
{
    /**
     * @var PDO
     */
    private $pdo;
    /**
     * @var ClassMetadata
     */
    private $metadataFactory;
    /**
     * @var array
     */
    private $deleteEvent = [];
    /**
     * @var array
     */
    private $insertEvent = [];

    /**
     * EntityManager constructor.
     * @param PDO $pdo
     * @param ClassMetadata $metadataFactory
     */
    public function __construct(PDO $pdo, ClassMetadata $metadataFactory)
    {
        $this->pdo = $pdo;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param string $className
     * @return array
     */
    public function getClassMetadata(string $className): array
    {
        return [
            'class' => $this->metadataFactory->getReflectionClass($className),
            'properties' => $this->metadataFactory->getReflectionProperity($className)
        ];
    }

    /**
     * @throws BadEntityException
     * @throws NotFoundRepositoryException
     */
    public function getRepository(string $entity): Repository
    {
        $metadataFactory = $this->getClassMetadata($entity);
        $metadataFactoryClass = $metadataFactory['class'];

        if (!isset($metadataFactoryClass['table'])) {
            throw new BadEntityException("Not found table annotation (@ORMLite\Table({\"here-table\"}))");
        }

        if (!in_array(EntityTrait::class, class_uses($entity))) {
            throw new BadEntityException(sprintf("The %s entity must use DevLancer\ORMLite\EntityTrait", $entity));
        }

        $repository = $metadataFactoryClass['repository'] ?? Repository::class;

        if (!class_exists($repository)) {
            throw new NotFoundRepositoryException(sprintf("Not found %s repository", $repository));
        }

        return new $repository($this, $entity);
    }

    /**
     * @throws BadEntityException
     * @throws NotFoundRepositoryException
     */
    public function find($entityName, $id): ?object
    {
        return $this->getRepository($entityName)->find($id);
    }

    /**
     *
     */
    public function clear(): void
    {
        Container::reset();
    }

    /**
     * @param object $entity
     */
    public function remove(object $entity):void
    {
        $this->deleteEvent[] = $entity;
    }

    /**
     * @param object $entity
     */
    public function persist(object $entity):void
    {
        $this->insertEvent[] = $entity;
    }

    /**
     *
     */
    public function flush():void
    {
        foreach ($this->insertEvent as $index => $entity) {
            unset($this->insertEvent[$index]);
            $metadataFactory = $this->getClassMetadata(get_class($entity));
            $columnId = $metadataFactory['class']['id'];
            $insertBuilder = new InsertBuilder($this, get_class($entity));
            foreach ($metadataFactory['properties'] as $property) {
                if (isset($columnId[0]) && $property['column'] === $columnId[0]) {
                    continue;
                }

                $value = $entity->{$property['column']};
                $value = $insertBuilder->convert($value, $property['type']);
                $insertBuilder->setParameter($property['column'], $value);
            }

            $insertBuilder->execute();
            if (isset($columnId[0])) {
                $id = $insertBuilder->lastInsertId();
                $entity->{$columnId[0]} = $insertBuilder->convert($id, $columnId[1]);
            }
        }

        foreach ($this->deleteEvent as $index => $entity) {
            unset($this->deleteEvent[$index]);
            $metadataFactory = $this->getClassMetadata(get_class($entity));
            $deleteBuilder = new DeleteBuilder($this, get_class($entity));
            foreach ($metadataFactory['properties'] as $property) {
                $value = $entity->{$property['column']};
                $value = $deleteBuilder->convert($value, $property['type']);
                $deleteBuilder
                    ->where(sprintf('%s = :%s', $property['column'], $property['column']))
                    ->setParameter($property['column'], $value);
            }

            $deleteBuilder->execute();
            unset($entity);
        }

        $updateEvent = Container::get();
        foreach ($updateEvent as $entity) {
            $metadataFactory = $this->getClassMetadata(get_class($entity));
            $updateBuilder = new UpdateBuilder($this, get_class($entity));
            $columnId = $metadataFactory['class']['id'];
            if ($columnId === null) {
                trigger_error(sprintf("The %s entity cannot be automatically updated because it does not have an identifier (primary key) defined.", get_class($entity)));
                continue;
            }

            $id = $entity->{$columnId[0]};
            $updateBuilder
                ->where(sprintf('%s = :%s', $columnId[0], $columnId[0]))
                ->setParameter($columnId[0], $id);

            foreach ($metadataFactory['properties'] as $property) {
                $value = $entity->{$property['column']};
                $value = $updateBuilder->convert($value, $property['type']);
                $updateBuilder
                    ->updateParameter($property['column'], $value);
            }

            $updateBuilder->execute();
        }
    }

    /**
     * @param string $entity
     * @return SelectBuilder
     */
    public function createQueryBuilder(string $entity): SelectBuilder
    {
        return new SelectBuilder($this, $entity);
    }

    /**
     * @param string $entity
     * @param string $sql
     * @return Query
     */
    public function createQuery(string $entity, string $sql): Query
    {
        $query = new Query($this, $entity);
        $query->setSql($sql);

        return $query;
    }

}