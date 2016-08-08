<?php

class Payment 
{
    // ----------------------------------------
    // 取得基本資料
    // ----------------------------------------
    function takeMemberData()
    {
        // ----------------------------------------
        // 定義使用者
        // ----------------------------------------
        
        $ID = 'Leif_Chen';
        
        // ----------------------------------------
        
        $db = new PDO("mysql:host=localhost;dbname=PayMent", "root", "");
        $db->exec("SET CHARACTER SET utf8");
        
        $eventList = "SELECT * FROM `MemberData` WHERE `MemberName` = :ID ;" ;
        $prepare = $db->prepare($eventList);
        $prepare->bindParam(':ID',$ID);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        return $result ;
    }
    // ----------------------------------------
    // 提(出)款
    // ----------------------------------------
    function dispensingMoney($money)
    {
        
    }
    // ----------------------------------------
    // 存(入)款
    // ----------------------------------------
    function depositMoney($money)
    {
        // ----------------------------------------
        // 定義使用者
        // ----------------------------------------
        
        $ID = 'Leif_Chen';
        
        // ----------------------------------------
        
        $db = new PDO("mysql:host=localhost;dbname=PayMent", "root", "");
        $db->exec("SET CHARACTER SET utf8");
        $db->beginTransaction();
        
        $eventList = "SELECT `totalAssets` FROM `MemberData` WHERE `MemberName` = :ID FOR UPDATE;" ;
        $prepare = $db->prepare($eventList);
        $prepare->bindParam(':ID', $ID);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        $nowMoney = $result[0]["totalAssets"];
        
        $totalMoney = $nowMoney + $money;
        
        // ----------------------------------------
        // 更新會員資料
        // ----------------------------------------
        
        $eventList = "UPDATE `MemberData` SET 
                    `totalAssets` = :totalMoney 
                    WHERE `MemberName` = :ID" ; 
        $prepare = $db->prepare($eventList);
        $prepare->bindParam(':totalMoney', $totalMoney);
        $prepare->bindParam(':ID', $ID);
        $prepare->execute();
        
        // ----------------------------------------
        // 更新動作明細
        // ----------------------------------------
        
        date_default_timezone_set('Asia/Taipei');
        $time = date("Y-m-d H:i:s") ;
        $action = 0 ;
        
        $eventList ="INSERT INTO `TransactionDetails` (
                            `MemberName` ,
							`dateTime` ,
							`preTotalAssets` ,
							`action` ,
							`money` ,
							`afterTotalAssets`
							) 
						VALUES ( 
						    :ID ,
							:time ,
							:preTotalAssets ,
							:action ,
							:money ,
							:afterTotalAssets
			            	)"; 
            
        $prepare = $db->prepare($eventList);
        $prepare->bindParam(':ID', $ID);
        $prepare->bindParam(':time', $time);
        $prepare->bindParam(':preTotalAssets', $nowMoney);
        $prepare->bindParam(':action', $action);
        $prepare->bindParam(':money', $money);
        $prepare->bindParam(':afterTotalAssets', $totalMoney);
        $prepare->execute();
        
        // ----------------------------------------  
        
        echo "<script language='JavaScript'>";
        echo "alert('存款完成');location.href='/_payment/';";
        echo "</script>";
        $db->commit();
    }
}

$MemberData = new Payment();

$basicData = $MemberData->takeMemberData();

if (isset($_POST["btnDispensing"])) 
{
    $MemberData->dispensingMoney($_POST["txtMoneyCount"]);
}
if (isset($_POST["btnDeposit"])) 
{
    $MemberData->depositMoney($_POST["txtMoneyCount"]);
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv = "Content-Type" content = "text/html ; charset = UTF-8">
    </head>
    <body>
        <?php foreach($basicData as $List): ?>
        帳號 : <?php echo $List["MemberName"]; ?><br>
        <br>
        餘額 : <?php echo $List["totalAssets"]; ?><br>
        <br>
        <?php endforeach ?>
        <form id = "formcreate" name = "formcreate" method = "post">
            執行動作 : 
            <input type="text" name="txtMoneyCount" id="txtMoneyCount"><br><br>
            <input type = "submit" name = "btnDispensing" id = "btnDispensing" value = "出款">
            &nbsp;<input type = "submit" name = "btnDeposit" id = "btnDeposit" value = "入款"><br>
        </form>
        <br>
        明細 : <br>
        <br>
        <table border = "1" width = "500px">
            <tr>
            <td width = "35%">時間</td>
            <td width = "15%">執行動作</td>
            <td width = "25%">金額</td>
            <td width = "25%">餘額</td>
            </tr>
        </table>
    </body>
</html>