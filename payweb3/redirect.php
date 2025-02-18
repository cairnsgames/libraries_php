<?php
include_once './config.php';
?>

<!-- PAY_REQUEST_ID : <?php echo $PAYREQUESTID; ?><br>
CHECKSUM : <?php echo $CHECKSUM; ?><br> -->

<form action="https://secure.paygate.co.za/payweb3/process.trans" method="POST" >
    <input name="PAY_REQUEST_ID" value="<?php echo $PAYREQUESTID ?>">
    <input name="CHECKSUM" value="<?php echo $CHECKSUM ?>">
    <button type="submit">Pay Now</button>
</form>