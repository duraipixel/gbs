<html>

<head>
    <title> Non-Seamless-kit</title>
</head>

<body>
    <center>

        <?php
        
        $merchant_data = '2';
        $working_key = 'B00B81683DCD0816F8F32551E2C2910B'; //Shared by CCAVENUES
        $access_code = 'AVRD71KE07CJ75DRJC'; //Shared by CCAVENUES
        
        foreach ($_POST as $key => $value) {
            $merchant_data .= $key . '=' . $value . '&';
        }
        
        $encrypted_data = encrypt($merchant_data, $working_key); // Method for encrypting the data.
        
        ?>
        <form method="post" name="redirect"
            action="https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction">
            <?php
            echo "<input type=hidden name=encRequest value=$encrypted_data>";
            echo "<input type=hidden name=access_code value=$access_code>";
            ?>
        </form>
    </center>
    <script language='javascript'>
        document.redirect.submit();
    </script>
</body>

</html>