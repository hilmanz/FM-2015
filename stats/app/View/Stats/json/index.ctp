<?php
if(isset($callbackFunction)):
?>
<?=$callbackFunction?>(<?=json_encode($response)?>);
<?php else:?>
<?=json_encode($response)?>
<?php endif;