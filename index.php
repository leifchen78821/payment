<?php

class Payment
{
    // ----------------------------------------
    // 定義帳號，資料庫
    // ----------------------------------------
    public $db = null;
    public $id = null;

    function __construct()
    {
        $this->id = 'Leif_Chen';
        $this->db = new PDO("mysql:host=localhost;dbname=PayMent", "root", "");
        $this->db->exec("SET CHARACTER SET utf8");
    }

    // ----------------------------------------
    // 取得基本資料
    // ----------------------------------------
    function takeMemberData()
    {
        $sql = "SELECT * FROM `MemberData` WHERE `memberName` = :id ;";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':id', $this->id);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    // ----------------------------------------
    // 取得明細資料
    // ----------------------------------------
    function takeTransactionDetails()
    {
        $sql = "SELECT * FROM `TransactionDetails` WHERE `memberName` = :id ;";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':id', $this->id);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    // ----------------------------------------
    // 提(出)款
    // ----------------------------------------
    function dispensingMoney($money)
    {
        try {
            $this->db->beginTransaction();
            $sql = "SELECT `totalAssets` FROM `MemberData` WHERE `memberName` = :id FOR UPDATE ;";
            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':id', $this->id);
            $prepare->execute();
            $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
            $nowMoney = $result[0]["totalAssets"];
    
            if ($nowMoney >= $money) {
    
                // ----------------------------------------
                // 更新會員資料
                // ----------------------------------------
    
                $totalMoney = $nowMoney - $money;
                $sql = "UPDATE `MemberData` SET `totalAssets` = :totalMoney WHERE `memberName` = :id"; 
                $prepare = $this->db->prepare($sql);
                $prepare->bindParam(':totalMoney', $totalMoney);
                $prepare->bindParam(':id', $this->id);
                $prepare->execute();
    
                // ----------------------------------------
                // 更新動作明細
                // ----------------------------------------
    
                date_default_timezone_set('Asia/Taipei');
                $time = date("Y-m-d H:i:s");
                $action = 1;
    
                $sql = "INSERT INTO `TransactionDetails` ".
                       "(`memberName`, `dateTime`, `preTotalAssets`, `action`, `money`, `afterTotalAssets`)".
                       "VALUES".
                       "(:id, :time, :preTotalAssets, :action, :money, :afterTotalAssets)";
    
                $prepare = $this->db->prepare($sql);
                $prepare->bindParam(':id', $this->id);
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
            }
        } catch (Exception $err) {
            $this->db->rollback();
        }
    }

    // ----------------------------------------
    // 存(入)款
    // ----------------------------------------
    function depositMoney($money)
    {
        try {
            $this->db->beginTransaction();
            $sql = "SELECT `totalAssets` FROM `MemberData` WHERE `memberName` = :id FOR UPDATE;";
            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':id', $this->id);
            $prepare->execute();
            $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
            $nowMoney = $result[0]["totalAssets"];
    
            // ----------------------------------------
            // 更新會員資料
            // ----------------------------------------
    
            $totalMoney = $nowMoney + $money;
            $sql = "UPDATE `MemberData` SET `totalAssets` = :totalMoney WHERE `memberName` = :id"; 
            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':totalMoney', $totalMoney);
            $prepare->bindParam(':id', $this->id);
            $prepare->execute();
    
            // ----------------------------------------
            // 更新動作明細
            // ----------------------------------------
    
            date_default_timezone_set('Asia/Taipei');
            $time = date("Y-m-d H:i:s");
            $action = 0;
    
            $sql = "INSERT INTO `TransactionDetails` ".
                   "(`memberName`, `dateTime`, `preTotalAssets`, `action`, `money`, `afterTotalAssets`)".
                   "VALUES".
                   "(:id, :time, :preTotalAssets, :action, :money, :afterTotalAssets)";
    
            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':id', $this->id);
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
        } catch (Exception $err) {
            $this->db->rollback();
        }
    }
}

$memberData = new Payment();

$basicMemberData = $memberData->takeMemberData();
$basicTransactionDetails = $memberData->takeTransactionDetails();

if (isset($_POST["btnDispensing"])) {
    $memberData->dispensingMoney($_POST["txtMoneyCount"]);
}

if (isset($_POST["btnDeposit"])) {
    $memberData->depositMoney($_POST["txtMoneyCount"]);
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv = "Content-Type" content = "text/html ; charset = UTF-8">
    </head>
    <body>
        <?php foreach($basicMemberData as $list): ?>
        帳號 : <?php echo $list["memberName"]; ?><br>
        <br>
        餘額 : <?php echo $list["totalAssets"]; ?><br>
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
            <?php foreach($basicTransactionDetails as $list): ?>
            <tr>
            <td width = "35%"><?php echo $list["dateTime"]; ?></td>
            <td width = "15%"><?php if($list["action"] == 0): ?>
                              存款
                              <?php else: ?>
                              提款
                              <?php endif ?></td>
            <td width = "25%"><?php echo $list["money"]; ?></td>
            <td width = "25%"><?php echo $list["afterTotalAssets"]; ?></td>
            </tr>
            <?php endforeach ?>
        </table>
    </body>
</html>