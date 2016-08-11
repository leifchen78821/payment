<?php
//**********************************************
// FOR UPDATE
//
// 加上排他鎖(FOR UPDATE)的資料，
// 其他連線能用普通的 select ... 讀取鎖定的資料，
// 但不能用 select ... lock in share mode 讀取鎖定的資料
// ( select ... from ... for update 當然也不行)。
//
// ----------------------------------------
// LOCK IN SHARE MODE
//
// 在 select 過程遇到的資料列加上共享鎖(LOCK IN SHARE MODE)。
// 加上共享鎖的資料，其他連線還是能讀取。
// 加上共享鎖的資料，也允許其他連線再執行 select ... lock in share mode。
//
//**********************************************
// PDO::FETCH_BOTH，(預設)可不寫
//
// 同時取得陣列key的編號與SQL欄位名稱
//
// ----------------------------------------
// PDO::FETCH_ASSOC
//
// 只取得欄位名稱
//
//**********************************************
// 資料庫寫法技巧
//
// 1. 資料庫計算可在sql語法內計算
// 2. 存提款可直接以 ±值 進行欄位存取，節省欄位
//
//**********************************************

// ----------------------------------------
// 此版本的資料庫排隊方式為悲觀鎖(Pessimistic Locking)
// FOR UPDATE
// ----------------------------------------

class Payment
{
    // ----------------------------------------
    // 定義帳號，資料庫
    // ----------------------------------------
    public $db = null;
    public $id = null;

    function __construct()
    {
        $this->id = $_GET["member"];
        $this->db = new PDO("mysql:host=localhost;dbname=PayMent", "root", "");
        $this->db->exec("SET CHARACTER SET utf8");
    }

    // ----------------------------------------
    // 新增使用者
    // ----------------------------------------
    function addNewMember($newMemberName)
    {
        $sql = "INSERT INTO `MemberData` " .
            "(`memberName`, `totalAssets`, `numberTicket`)" .
            "VALUES " .
            "(:newMemberName, '0', '1');";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':newMemberName', $newMemberName);
        $prepare->execute();
        echo "<script language='JavaScript'>";
        echo "alert('新增使用者 : " . $newMemberName . " 成功');location.href='/_payment/index_Pessimistic_Locking.php';";
        echo "</script>";
    }

    // ----------------------------------------
    // 取得下拉選單資料
    // ----------------------------------------
    function takeMemberList()
    {
        $sql = "SELECT `memberName` FROM `MemberData`";
        $prepare = $this->db->prepare($sql);
        $prepare->execute();
        $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    // ----------------------------------------
    // 依據下拉式選單選擇後取得新id
    // ----------------------------------------
    function selectMember($memberSelected)
    {
        echo "<script language='JavaScript'>";
        echo "alert('選擇使用者 : " . $memberSelected . "');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $memberSelected . "';";
        echo "</script>";
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
            $sql = "SELECT `totalAssets` FROM `MemberData` WHERE `memberName` = :id FOR UPDATE;";
            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':id', $this->id);
            $prepare->execute();
            $result = $prepare->fetch(PDO::FETCH_ASSOC);
            $nowMoney = $result["totalAssets"];

            if ($nowMoney >= $money) {
                // ----------------------------------------
                // 更新會員資料
                // ----------------------------------------
                $sql = "UPDATE `MemberData` SET `totalAssets` = `totalAssets` - :money WHERE `memberName` = :id";
                $prepare = $this->db->prepare($sql);
                $prepare->bindParam(':money', $money);
                $prepare->bindParam(':id', $this->id);
                $prepare->execute();

                // ----------------------------------------
                // 更新動作明細
                // ----------------------------------------
                date_default_timezone_set('Asia/Taipei');
                $time = date("Y-m-d H:i:s");

                $sql = "INSERT INTO `TransactionDetails` " .
                    "(`memberName`, `dateTime`, `money`, `endActionTotalAssets`)" .
                    "VALUES" .
                    "(:id, :time, - :money, :nowMoney - :money)";

                $prepare = $this->db->prepare($sql);
                $prepare->bindParam(':id', $this->id);
                $prepare->bindParam(':time', $time);
                $prepare->bindParam(':nowMoney', $nowMoney);
                $prepare->bindParam(':money', $money);
                $prepare->execute();

                echo "<script language='JavaScript'>";
                echo "alert('出款完成');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $_GET["member"] . "';";
                echo "</script>";
            } else {
                echo "<script language='JavaScript'>";
                echo "alert('出款失敗');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $_GET["member"] . "';";
                echo "</script>";
            }
            $this->db->commit();
        } catch (Exception $err) {
            echo "<script language='JavaScript'>";
            echo "alert('" . $err->getMessage() . "');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $_GET["member"] . "';";
            echo "</script>";
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
            $result = $prepare->fetch(PDO::FETCH_ASSOC);
            $nowMoney = $result["totalAssets"];

            // ----------------------------------------
            // 更新會員資料
            // ----------------------------------------
            $sql = "UPDATE `MemberData` SET `totalAssets` = `totalAssets` + :money WHERE `memberName` = :id";
            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':money', $money);
            $prepare->bindParam(':id', $this->id);
            $prepare->execute();


            // ----------------------------------------
            // 更新動作明細
            // ----------------------------------------
            date_default_timezone_set('Asia/Taipei');
            $time = date("Y-m-d H:i:s");
            $action = 0;

            $sql = "INSERT INTO `TransactionDetails` " .
                "(`memberName`, `dateTime`, `money`, `endActionTotalAssets`)" .
                "VALUES" .
                "(:id, :time, :money, :nowMoney + :money)";

            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':id', $this->id);
            $prepare->bindParam(':time', $time);
            $prepare->bindParam(':nowMoney', $nowMoney);
            $prepare->bindParam(':money', $money);
            $prepare->execute();

            echo "<script language='JavaScript'>";
            echo "alert('入款完成');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $_GET["member"] . "';";
            echo "</script>";
            $this->db->commit();
        } catch (Exception $err) {
            echo "<script language='JavaScript'>";
            echo "alert('" . $err->getMessage() . "');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $_GET["member"] . "';";
            echo "</script>";
            $this->db->rollback();
        }
    }
}

$memberData = new Payment();

$basicMemberData = $memberData->takeMemberData();
$basicMemberList = $memberData->takeMemberList();
$basicTransactionDetails = $memberData->takeTransactionDetails();

// ----------------------------------------
// 新增會員按鈕
// ----------------------------------------
if (isset($_POST["btnAddMember"])) {
    $memberData->addNewMember($_POST["txtAddNewMember"]);
}

// ----------------------------------------
// 選擇會員按鈕
// ----------------------------------------
if (isset($_POST["btnSelectMember"])) {
    $memberData->selectMember($_POST["select_one"]);
}

// ----------------------------------------
// 出款按鈕
// ----------------------------------------
if (isset($_POST["btnDispensing"])) {
    if ($_POST["txtMoneyCount"] <= 0) {
        echo "<script language='JavaScript'>";
        echo "alert('輸入金額不可低於0');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $_GET["member"] . "';";
        echo "</script>";
    } else {
        $memberData->dispensingMoney($_POST["txtMoneyCount"]);
    }
}

// ----------------------------------------
// 入款按鈕
// ----------------------------------------
if (isset($_POST["btnDeposit"])) {
    if ($_POST["txtMoneyCount"] <= 0) {
        echo "<script language='JavaScript'>";
        echo "alert('輸入金額不可低於0');location.href='/_payment/index_Pessimistic_Locking.php?member=" . $_GET["member"] . "';";
        echo "</script>";
    } else {
        $memberData->depositMoney($_POST["txtMoneyCount"]);
    }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv = "Content-Type" content = "text/html ; charset = UTF-8">
    </head>
    <body>
        <form id = "formcreate" name = "formcreate" method = "post">
        <br>---------------------------------------------------------------------<br><br>
        新增使用者 :
        <input type = "text" name = "txtAddNewMember" id = "txtAddNewMember">
        <input type = "submit" name = "btnAddMember" id = "btnAddMember" value = "新增"><br><br>
        ---------------------------------------------------------------------<br><br>
        選擇帳號 :
        <select name = "select_one">
        <?php foreach($basicMemberList as $list): ?>
        <option value = <?php echo $list["memberName"]; ?>><?php echo $list["memberName"]; ?></option>
        <?php endforeach ?>
        </select>
        <input type = "submit" name = "btnSelectMember" id = "btnSelectMember" value = "選擇">
        <br><br>---------------------------------------------------------------------<br><br>
        <?php foreach($basicMemberData as $list): ?>
        帳號 : <?php echo $list["memberName"]; ?><br>
        <br>
        餘額 : <?php echo $list["totalAssets"]; ?><br>
        <br>
        <?php endforeach ?>
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
            <td width = "15%"><?php if($list["money"] > 0): ?>
                              存款
                              <?php else: ?>
                              提款
                              <?php endif ?></td>
            <td width = "25%"><?php echo abs($list["money"]); ?></td>
            <td width = "25%"><?php echo $list["endActionTotalAssets"]; ?></td>
            </tr>
            <?php endforeach ?>
        </table>
    </body>
</html>