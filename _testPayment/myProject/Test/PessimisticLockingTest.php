<?php

require_once "myProject/IndexPessimisticLocking.php";

class PessimisticLockingTest extends \PHPUnit_Framework_TestCase
{
    public $db = null;
    public $id = null;

    protected function setUp()
    {
        $this->id = 'Lid_Chen';
        $this->db = new PDO("mysql:host=localhost;dbname=PayMent", "root", "");
        $this->db->exec("SET CHARACTER SET utf8");

        $sql = "INSERT INTO `MemberData` " .
            "(`memberName`, `totalAssets`, `numberTicket`)" .
            "VALUES " .
            "('Lid_Chen' , '10000', '1');";
        $prepare = $this->db->prepare($sql);
        $prepare->execute();
    }

    // 測試新增會員
    public function testAddNewMemberSuccess() {
        $payment = new Payment();
        $newMemberName = 'Anny';
        $expectedResult = "新增使用者 : Anny 成功";
        $result = $payment->addNewMember($newMemberName);

        $this->assertEquals($expectedResult, $result);
    }

    // 測試取得會員資料
    public function testTakeMemberDataSuccess() {
        $payment = new Payment();
        $expectedResult = 490000;
        $result = $payment->takeMemberData();

        $this->assertEquals($expectedResult, $result);
    }

    // 測試提款數量在餘額範圍內
    public function testDispensingMoneySuccess() {
        $payment = new Payment();
        $money = 1000;
        $expectedResult = "出款完成";
        $result = $payment->dispensingMoney($money);

        $this->assertEquals($expectedResult, $result);
    }

    // 測試提款數量在餘額範圍外
    public function testDispensingMoneyFail() {
        $payment = new Payment();
        $money = 99999999999999;
        $expectedResult = "出款失敗";
        $result = $payment->dispensingMoney($money);

        $this->assertEquals($expectedResult, $result);
    }

    // 測試存款
    public function testDepositMoneySuccess() {
        $payment = new Payment();
        $money = 10000;
        $expectedResult = "入款完成";
        $result = $payment->depositMoney($money);

        $this->assertEquals($expectedResult, $result);
    }

    protected function tearDown()
    {
        $sql = "DELETE FROM `MemberData` WHERE `memberName` = 'Lid_Chen'" ;
        $prepare = $this->db->prepare($sql);
        $prepare->execute();
        $sql = "DELETE FROM `TransactionDetails` WHERE `memberName` = 'Lid_Chen'" ;
        $prepare = $this->db->prepare($sql);
        $prepare->execute();
    }
}

?>