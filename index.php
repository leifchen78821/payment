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
}

$MemberData = new Payment();
$basicData = $MemberData->takeMemberData();

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
        <form>
            執行動作 : 
            <input type="text" name="txtMoneyCount" id="txtMoneyCount"><br><br>
            <input type = "button" name = "btnDispensing" id = "btnDispensing" value = "出款">
            &nbsp;<input type = "button" name = "btnDeposit" id = "btnDeposit" value = "入款"><br>
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