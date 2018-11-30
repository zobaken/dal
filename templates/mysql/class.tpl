/**
* Class to work with table '<?=$tableName?>'.
* Will not be overwritten. You can make changes here.
*/

<?php if($namespace): ?>
namespace <?=$namespace?>;
<?php else: ?>
require_once __DIR__ . '/Table/<?=$tableClassName?>.php';
<?php endif; ?>

class <?=$className?> extends Table\<?=$tableClassName?> {

}
