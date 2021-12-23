<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ORMLite\Query;


class DeleteBuilder extends Builder
{

    public function generate()
    {
        $sql = sprintf("DELETE FROM %s WHERE %s", $this->table, $this->_generateWhere());
        $this->setSql($sql);
    }
}