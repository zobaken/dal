/**
* Class to work with table '<?=$tableName?>'.
* Will not be overwritten. You can make changes here.
*/

<?php if($namespace): ?>
namespace <?=$namespace?>;
<?php endif; ?>

require_once __DIR__ . '/Table/<?=$tableClassName?>Prototype.php';

class <?=$className?> extends Table\<?=$tableClassName?>Prototype {

}
