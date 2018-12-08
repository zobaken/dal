/**
 * Helper class to work with table "<?=$tableName?>".
 * Generated automatically. All changes will be lost.
*/

<?php if($namespace): ?>
namespace <?=$namespace?>\Table;
<?php else: ?>
namespace Table;
<?php endif; ?>

class <?=$tableClassName?> extends \Dal\Model\Prototype {

    static $fields;
    static $table = '<?=$tableName?>';
    static $pk = [<?=implode(', ', $pk)?>];
    static $profile = '<?=$profile?>';
<?php if($generated): ?>
    static $generated = [
<?php foreach($generated as $field=>$generator): ?>
'<?=$field?>' => '<?=$generator?>',
<?php endforeach;?>
];
<?php else: ?>
    static $generated = [];
<?php endif; ?>
<?php foreach($tableInfo as $field):?>

    /**
    * Field: <?=$tableName?>.<?=$field['Field']."\n"?>
    * Type: <?=$field['Type']."\n"?>
<?php if(preg_match('/int/', $field['Type'])): ?>
    * @var int
<?php else: ?>
    * @var string
<?php endif; ?>
    */
<?php if(preg_match('/^int/', $field['Type']) && $field['Default'] !== null): ?>
    public $<?=$field['Field']?> = <?=$field['Default']?>;
<?php elseif(preg_match('/^(char|varchar)/', $field['Type']) && $field['Default'] !== null): ?>
    public $<?=$field['Field']?> = '<?=addcslashes($field['Default'], "'")?>';
<?php else: ?>
    public $<?=$field['Field']?>;
<?php endif; ?>
<?php endforeach;?>

    /**
    * Get object by id
    * @param mixed $id Id
    * @return <?=$namespace ? '\\$namespace\\' : ''?><?=$className?>

    */
    static function get($id) {
        return forward_static_call_array(['\Dal\Model\Prototype', 'get'], func_get_args());
    }

    /**
    * Get all objects
    * @param string $order Order expression
    * @return <?=$namespace ? '\\$namespace\\' : ''?><?=$className?>[]
    */
    static function getAll($order = null) {
        return forward_static_call_array(['\Dal\Model\Prototype', 'getAll'], func_get_args());
    }

    /**
    * Find object
    * @param string $where Where statement
    * @return <?=$namespace ? '\\$namespace\\' : ''?><?=$className?>

    */
    static function findRow($where) {
        return forward_static_call_array(['\Dal\Model\Prototype', 'findRow'], func_get_args());
    }

    /**
    * Find objects
    * @param string $where Where statement
    * @return <?=$namespace ? '\\$namespace\\' : ''?><?=$className?>[]
    */
    static function find($where) {
        return forward_static_call_array(['\Dal\Model\Prototype', 'find'], func_get_args());
    }

}
