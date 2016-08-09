<?php

class Payment 
{   
    // ----------------------------------------
    // 定義帳號，資料庫
    // ----------------------------------------
    public $db = null;
    public $ID = null;

    function __construct()
    {
        $this->ID = 'Leif_Chen';
        $this->db = new PDO("mysql:host=localhost;dbname=PayMent", "root", "");
        $this->db->exec("SET CHARACTER SET utf8");
    }

    // ----------------------------------------
    // 取得基本資料
    // ----------------------------------------
    function takeMemberData()
    {
        $eventList = "SELECT * FROM `MemberData` WHERE `MemberName` = :ID ;";
        $prepare = $this->db->prepare($eventList);
        $prepare->bindParam(':ID',$this->ID);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    // ----------------------------------------
    // 取得明細資料
    // ----------------------------------------
    function takeMemberList()
    {
        $eventList = "SELECT * FROM `TransactionDetails` WHERE `MemberName` = :ID ;";
        $prepare = $this->db->prepare($eventList);
        $prepare->bindParam(':ID',$this->ID);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    // ----------------------------------------
    // 提(出)款
    // ----------------------------------------
    function dispensingMoney($money)
    {
        $this->db->beginTransaction();
        $eventList = "SELECT `totalAssets` FROM `MemberData` WHERE `MemberName` = :ID FOR UPDATE ;";
        $prepare = $this->db->prepare($eventList);
        $prepare->bindParam(':ID', $this->ID);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        $nowMoney = $result[0]["totalAssets"];

        if($nowMoney >= $money) {

            // ----------------------------------------
            // 更新會員資料
            // ----------------------------------------

            $totalMoney = $nowMoney - $money;

            $eventList = "UPDATE `MemberData` SET 
                                `totalAssets` = :totalMoney 
                                WHERE 
                                `MemberName` = :ID"; 

            $prepare = $this->db->prepare($eventList);
            $prepare->bindParam(':totalMoney', $totalMoney);
            $prepare->bindParam(':ID', $this->ID);
            $prepare->execute();

            // ----------------------------------------
            // 更新動作明細
            // ----------------------------------------

            date_default_timezone_set('Asia/Taipei');
            $time = date("Y-m-d H:i:s");
            $action = 1;

            $eventList = "INSERT INTO `TransactionDetails` (
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

            echo "<script language='JavaScript'>";
            echo "alert('出款完成');location.href='/_payment/';";
            echo "</script>";
            $this->db->commit();
        } else {
            echo "<script language='JavaScript'>";
            echo "alert('出款失敗');location.href='/_payment/';";
            echo "</script>";
            $this->db->rollback();
        }

    // ----------------------------------------
    // 存(入)款
    // ----------------------------------------
    function depositMoney($money)
    {
        $this->db->beginTransaction();
        $eventList = "SELECT `totalAssets` FROM `MemberData` WHERE `MemberName` = :ID FOR UPDATE;";
        $prepare = $this->db->prepare($eventList);
        $prepare->bindParam(':ID', $this->ID);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        $nowMoney = $result[0]["totalAssets"];

        // ----------------------------------------
        // 更新會員資料
        // ----------------------------------------

        $totalMoney = $nowMoney + $money;
        
        $eventList = "UPDATE `MemberData` SET 
                            `totalAssets` = :totalMoney 
                            WHERE 
                            `MemberName` = :ID"; 

        $prepare = $this->db->prepare($eventList);
        $prepare->bindParam(':totalMoney', $totalMoney);
        $prepare->bindParam(':ID', $this->ID);
        $prepare->execute();
        
        // ----------------------------------------
        // 更新動作明細
        // ----------------------------------------
        
        date_default_timezone_set('Asia/Taipei');
        $time = date("Y-m-d H:i:s");
        $action = 0;
        
        $eventList = "INSERT INTO `TransactionDetails` (
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
            
        $prepare = $this->db->prepare($eventList);
        $prepare->bindParam(':ID', $this->ID);
        $prepare->bindParam(':time', $time);
        $prepare->bindParam(':preTotalAssets', $nowMoney);
        $prepare->bindParam(':action', $action);
        $prepare->bindParam(':money', $money);
        $prepare->bindParam(':afterTotalAssets', $totalMoney);
        $prepare->execute();

        echo "<script language='JavaScript'>";
        echo "alert('入款完成');location.href='/_payment/';";
        echo "</script>";
        $this->db->commit();
    }
}

$MemberData = new Payment();

$basicData = $MemberData->takeMemberData();
$basicList = $MemberData->takeMemberList();

if (isset($_POST["btnDispensing"])) {
    $MemberData->dispensingMoney($_POST["txtMoneyCount"]);
}

if (isset($_POST["btnDeposit"])) {
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
            <?php foreach($basicList as $List): ?>
            <tr>
            <td width = "35%"><?php echo $List["dateTime"]; ?></td>
            <td width = "15%"><?php if($List["action"] == 0): ?>
                              存款
                              <?php else: ?>
                              提款
                              <?php endif ?></td>
            <td width = "25%"><?php echo $List["money"]; ?></td>
            <td width = "25%"><?php echo $List["afterTotalAssets"]; ?></td>
            </tr>
            <?php endforeach ?>
        </table>
    </body>
</html>