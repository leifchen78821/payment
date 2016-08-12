<?php

class Payment
{
    // 定義帳號，資料庫
    public $db = null;
    public $id = null;

    function __construct()
    {
        date_default_timezone_set('Asia/Taipei');
        $this->id = 'Leif_Chen';
        $this->db = new PDO("mysql:host=localhost;dbname=PayMent", "root", "");
        $this->db->exec("SET CHARACTER SET utf8");
    }

    // 新增使用者
    function addNewMember($newMemberName)
    {
        $sql = "INSERT INTO `MemberData` " .
            "(`memberName`, `totalAssets`, `numberTicket`)" .
            "VALUES " .
            "(:newMemberName, '0', '1');";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':newMemberName', $newMemberName);
        // $prepare->execute();

        // echo "<script language='JavaScript'>";
        // echo "alert('新增使用者 : " . $newMemberName . " 成功');location.href='/_payment/IndexPessimisticLocking.php';";
        // echo "</script>";

        return "新增使用者 : " . $newMemberName . " 成功" ;

    }

    // 取得基本資料
    function takeMemberData()
    {
        $sql = "SELECT * FROM `MemberData` WHERE `memberName` = :id";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':id', $this->id);
        $prepare->execute();
        $result = $prepare->fetch(PDO::FETCH_ASSOC);

        $totalAssets = $result["totalAssets"];

        return $totalAssets;
    }

    // 提(出)款
    function dispensingMoney($money)
    {
        $this->db->beginTransaction();
        $sql = "SELECT `totalAssets` FROM `MemberData` WHERE `memberName` = :id FOR UPDATE";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':id', $this->id);
        $prepare->execute();
        $result = $prepare->fetch(PDO::FETCH_ASSOC);
        $nowMoney = $result["totalAssets"];

        if ($nowMoney >= $money) {
            // 更新會員資料
            $sql = "UPDATE `MemberData` SET `totalAssets` = `totalAssets` - :money WHERE `memberName` = :id";
            $prepare = $this->db->prepare($sql);
            $prepare->bindParam(':money', $money);
            $prepare->bindParam(':id', $this->id);
            $prepare->execute();

            // 更新動作明細
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

            // echo "<script language='JavaScript'>";
            // echo "alert('出款完成');location.href='/_payment/IndexPessimisticLocking.php?member=" . $_GET["member"] . "';";
            // echo "</script>";

            return '出款完成';
        } else {
            // echo "<script language='JavaScript'>";
            // echo "alert('出款失敗');location.href='/_payment/IndexPessimisticLocking.php?member=" . $_GET["member"] . "';";
            // echo "</script>";

            return '出款失敗';
        }
        $this->db->rollback();
    }

    // 存(入)款
    function depositMoney($money)
    {
        $this->db->beginTransaction();
        $sql = "SELECT `totalAssets` FROM `MemberData` WHERE `memberName` = :id FOR UPDATE";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':id', $this->id);
        $prepare->execute();
        $result = $prepare->fetch(PDO::FETCH_ASSOC);
        $nowMoney = $result["totalAssets"];

        // 更新會員資料
        $sql = "UPDATE `MemberData` SET `totalAssets` = `totalAssets` + :money WHERE `memberName` = :id";
        $prepare = $this->db->prepare($sql);
        $prepare->bindParam(':money', $money);
        $prepare->bindParam(':id', $this->id);
        $prepare->execute();

        // 更新動作明細
        $time = date("Y-m-d H:i:s");

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

        $this->db->rollback();

        // echo "<script language='JavaScript'>";
        // echo "alert('入款完成');location.href='/_payment/IndexPessimisticLocking.php?member=" . $_GET["member"] . "';";
        // echo "</script>";

        return '入款完成';
    }
}
