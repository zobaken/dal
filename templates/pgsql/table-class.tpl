/**
 * Helper class to work with table "<?=$tableName?>".
 * Generated automatically. All changes will be lost.
*/

<?php if($namespace): ?>
namespace <?=$namespace?>\Table;
<?php else: ?>
namespace Table;
<?php endif; ?>

class <?=$tableClassName?> extends \Dal\Model\AbstractTable {

    static $columns;
    static $table = '<?=$tableName?>';
    static $pk = [<?=implode(', ', $pk)?>];
<?php if($generated): ?>
    static $generated = [
<?php foreach($generated as $column=>$generator): ?>
'<?=$column?>' => '<?=$generator?>',
<?php endforeach;?>
];
<?php else: ?>
    static $generated = [];
<?php endif; ?>
    static $sequences = [<?= $sequences ?>];
<?php foreach($tableColumns as $column):?>

    /**
    * Field: <?=$tableName?>.<?=$column['column_name']."\n"?>
    * Type: <?=$column['data_type']."\n"?>
<?php if($column['data_type'] == 'integer'): ?>
    * @var int
<?php else: ?>
    * @var string
<?php endif; ?>
    */
<?php if($column['data_type'] == 'integer' && $column['column_default'] !== null
        && substr($column['column_default'], 0, 7) != 'nextval' ): ?>
    public $<?=$column['column_name']?> = <?=$column['column_default']?>;
<?php elseif(preg_match('/^(character)/', $column['data_type']) && $column['column_default'] !== null): ?>
    public $<?=$column['column_name']?> = '<?=addcslashes($column['column_default'], "'")?>';
<?php else: ?>
    public $<?=$column['column_name']?>;
<?php endif; ?>
<?php endforeach;?>

    /**
    * Get object by id
    * @param mixed $id Id
    * @return \<?=$namespace?>\<?=$className?>
    */
    static function get($id) {
        return forward_static_call_array(['\Dal\Model\AbstractTable', 'get'], func_get_args());
    }

    /**
    * Get all objects
    * @param string $order Order expression
    * @return \<?=$namespace?>\<?=$className?>
    */
    static function getAll($order = null) {
        return forward_static_call_array(['\Dal\Model\AbstractTable', 'get'], func_get_args());
    }

    /**
    * Find object
    * @param string $where Where statement
    * @return \<?=$namespace?>\<?=$className?>
    */
    static function findRow($where) {
        return forward_static_call_array(['\Dal\Model\AbstractTable', 'findRow'], func_get_args());
    }

    /**
    * Find objects
    * @param string $where Where statement
    * @return \<?=$namespace?>\<?=$className?>[]
    */
    static function find($where) {
        return forward_static_call_array(['\Dal\Model\AbstractTable', 'find'], func_get_args());
    }

}
