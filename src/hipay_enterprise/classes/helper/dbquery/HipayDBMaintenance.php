<?php

/**
 * HiPay Enterprise SDK Prestashop.
 *
 * 2017 HiPay
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay <support.tpp@hipay.com>
 * @copyright 2017 HiPay
 * @license   https://github.com/hipay/hipay-enterprise-sdk-prestashop/blob/master/LICENSE.md
 */

use HiPay\Fullservice\Enum\Transaction\TransactionStatus;

require_once dirname(__FILE__).'/HipayDBQueryAbstract.php';
require_once dirname(__FILE__).'/../enums/NotificationStatus.php';

/**
 * @author      HiPay <support.tpp@hipay.com>
 * @copyright   Copyright (c) 2017 - HiPay
 * @license     https://github.com/hipay/hipay-enterprise-sdk-prestashop/blob/master/LICENSE.md
 *
 * @see    https://github.com/hipay/hipay-enterprise-sdk-prestashop
 */
class HipayDBMaintenance extends HipayDBQueryAbstract
{
    /**
     * save order capture data (basket).
     *
     * @param array<string,mixed> $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function setCaptureOrRefundOrder($values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = pSQL($value);
        }

        return Db::getInstance()->insert(
            HipayDBQueryAbstract::HIPAY_ORDER_REFUND_CAPTURE_TABLE,
            $values
        );
    }

    /**
     * get order capture saved data (basket).
     *
     * @param int $orderId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getCapturedItems($orderId)
    {
        return $this->getMaintainedItems($orderId, 'capture', 'good');
    }

    /**
     * get order refund saved data (basket).
     *
     * @param int $orderId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getRefundedItems($orderId)
    {
        return $this->getMaintainedItems($orderId, 'refund', 'good');
    }

    /**
     * return true if a capture or refund have been executed from TPP BO.
     *
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function captureOrRefundFromBO($orderId)
    {
        return !empty($this->getMaintainedItems($orderId, 'BO_TPP', 'BO'));
    }

    /**
     * get number of capture or refund attempt.
     *
     * @param string $operation
     * @param int    $orderId
     *
     * @return int
     */
    public function getNbOperationAttempt($operation, $orderId)
    {
        $sql = 'SELECT `attempt_number`'
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_ORDER_REFUND_CAPTURE_TABLE.'`'
            .' WHERE `hp_ps_order_id` = '.(int) $orderId
            .' AND `operation` = "'.pSQL($operation).'"'
            .' ORDER BY `attempt_number` DESC';

        $result = Db::getInstance()->getRow($sql);
        if (isset($result['attempt_number'])) {
            return (int) $result['attempt_number'];
        }

        return 0;
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function feesAreCaptured($orderId)
    {
        return $this->feesOrDiscountAreMaintained($orderId, 'fees', 'capture');
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function feesAreRefunded($orderId)
    {
        return $this->feesOrDiscountAreMaintained($orderId, 'fees', 'refund');
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function discountsAreCaptured($orderId)
    {
        return $this->feesOrDiscountAreMaintained($orderId, 'discount', 'capture');
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function discountsAreRefunded($orderId)
    {
        return $this->feesOrDiscountAreMaintained($orderId, 'discount', 'refund');
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function wrappingIsRefunded($orderId)
    {
        return $this->feesOrDiscountAreMaintained($orderId, 'wrapping', 'refund');
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function wrappingIsCaptured($orderId)
    {
        return $this->feesOrDiscountAreMaintained($orderId, 'wrapping', 'capture');
    }

    /**
     * save order capture type.
     *
     * @param array<string,mixed> $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function setOrderCaptureType($values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = pSQL($value);
        }

        return Db::getInstance()->insert(HipayDBQueryAbstract::HIPAY_ORDER_CAPTURE_TYPE_TABLE, $values);
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function OrderCaptureTypeExist($orderId)
    {
        $sql = 'SELECT * '
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_ORDER_CAPTURE_TYPE_TABLE.'`'
            .' WHERE order_id = '.(int) $orderId;

        return !empty(Db::getInstance()->executeS($sql));
    }

    /**
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function isManualCapture($orderId)
    {
        $sql = 'SELECT * '
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_ORDER_CAPTURE_TYPE_TABLE.'`'
            .' WHERE order_id = '.(int) $orderId
            .' AND type = "manual"'
            .' LIMIT 1';

        return !empty(Db::getInstance()->executeS($sql));
    }

    /**
     * return if order already captured from hipay transaction.
     *
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function alreadyCaptured($orderId)
    {
        $sql = 'SELECT *'
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_TRANSACTION_TABLE.'`'
            .' WHERE order_id = '.(int) $orderId
            .' AND status = '.TransactionStatus::CAPTURED;

        return !empty(Db::getInstance()->executeS($sql));
    }

    /**
     * save hipay transaction (notification).
     *
     * @param array<string,mixed> $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function setHipayTransaction($values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = pSQL($value);
        }

        return Db::getInstance()->insert(HipayDBQueryAbstract::HIPAY_TRANSACTION_TABLE, $values);
    }

    /**
     * return order transaction reference from hipay transaction.
     *
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function getTransactionReference($orderId)
    {
        $sql = 'SELECT *'
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_TRANSACTION_TABLE.'`'
            .' WHERE order_id = '.(int) $orderId
            .' AND status IN ('.TransactionStatus::AUTHORIZED.', '.TransactionStatus::AUTHORIZED_AND_PENDING.')'
            .' LIMIT 1';

        if (!empty($result = Db::getInstance()->executeS($sql))) {
            return $result[0]['transaction_ref'];
        }

        return false;
    }

    /**
     * return true if cancel notification has ever been received for order.
     *
     * @param int $orderId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function isTransactionCancelled($orderId)
    {
        $sql = 'SELECT *'
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_TRANSACTION_TABLE.'`'
            .' WHERE order_id = '.(int) $orderId
            .' AND status IN ('.TransactionStatus::CANCELLED.', '.TransactionStatus::AUTHORIZATION_CANCELLATION_REQUESTED.')'
            .' LIMIT 1';

        return !empty(Db::getInstance()->executeS($sql));
    }

    /**
     * return order transaction from hipay transaction.
     *
     * @param string $transaction_reference
     */
    public function getTransactionById($transaction_reference)
    {
        $sql = 'SELECT *'
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_TRANSACTION_TABLE.'`'
            .' WHERE transaction_ref = "'.pSQL($transaction_reference).'"'
            .' LIMIT 1';

        if (!empty($result = Db::getInstance()->executeS($sql))) {
            return $result[0];
        }

        return false;
    }

    /**
     * return order payment product from hipay transaction.
     *
     * @param int $orderId
     *
     * @return string|bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function getPaymentProductFromMessage($orderId)
    {
        $sql = 'SELECT *'
            .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_TRANSACTION_TABLE.'`'
            .' WHERE order_id = '.(int) $orderId
            .' AND status IN ('.TransactionStatus::AUTHORIZED.', '.TransactionStatus::AUTHORIZED_AND_PENDING.')'
            .' LIMIT 1';

        if (!empty($result = Db::getInstance()->executeS($sql))) {
            return $result[0]['payment_product'];
        }

        return false;
    }

    /**
     * return order basket from hipay transaction.
     *
     * @param int $orderId
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     */
    public function getOrderBasket($orderId)
    {
        $sql = 'SELECT *'
        .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_TRANSACTION_TABLE.'`'
        .' WHERE order_id = '.(int) $orderId
        .' AND status ='.TransactionStatus::AUTHORIZED
        .' LIMIT 1';

        if (!empty($result = Db::getInstance()->executeS($sql))) {
            return json_decode($result[0]['basket'], true);
        }

        return false;
    }

    /**
     * get capture or refund saved data (basket).
     *
     * @param int $orderId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    private function getMaintainedItems($orderId, $operation, $type)
    {
        $sql = 'SELECT `hp_ps_product_id`, `operation`, `type`, SUM(`quantity`) as quantity, SUM(`amount`) as amount'
        .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_ORDER_REFUND_CAPTURE_TABLE.'`'
        .' WHERE `hp_ps_order_id` = '.(int) $orderId
        .' AND `operation` = "'.pSQL($operation).'"'
        .' AND `type` = "'.pSQL($type).'"'
        .' GROUP BY `hp_ps_product_id`';

        $result = Db::getInstance()->executeS($sql);
        $formattedResult = [];
        foreach ($result as $item) {
            $formattedResult[$item['hp_ps_product_id']] = $item;
        }

        return $formattedResult;
    }

    /**
     * @param int    $orderId
     * @param string $type
     * @param string $operation
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    private function feesOrDiscountAreMaintained($orderId, $type, $operation)
    {
        $sql = 'SELECT *'
        .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_ORDER_REFUND_CAPTURE_TABLE.'`'
        .' WHERE `hp_ps_order_id` = '.(int) $orderId
        .' AND `operation` = "'.pSQL($operation).'"'
        .' AND `type` = "'.pSQL($type).'"';

        return !empty(Db::getInstance()->executeS($sql));
    }

    /**
     * @param array<string,mixed>
     *
     * @return int|false
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function getNotificationAttempt(array $data)
    {
        $sql = 'SELECT attempt_number'
        .' FROM `'._DB_PREFIX_.HipayDBQueryAbstract::HIPAY_NOTIFICATION_TABLE.'`'
        .' WHERE `cart_id` = '.(int) $data['cart_id']
        .' AND `notification_code` = '.(int) $data['notification_code']
        .' AND `transaction_ref` = "'.pSQL($data['transaction_ref']).'"'
        .' AND `status` NOT IN ("'.NotificationStatus::SUCCESS.'", "'.NotificationStatus::NOT_HANDLED.'")';

        if (empty($result = Db::getInstance()->executeS($sql))) {
            return false;
        }

        return (int) $result[0]['attempt_number'];
    }

    /**
     * @param array<string,mixed> $data
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function saveHipayNotification(array $data)
    {
        $safeData = [];
        foreach ($data as $key => $value) {
            $safeData[$key] = pSQL($value);
        }

        if (1 === $data['attempt_number']) {
            return Db::getInstance()->insert(HipayDBQueryAbstract::HIPAY_NOTIFICATION_TABLE, $safeData);
        } else {
            $where = '`cart_id` = '.(int) $data['cart_id']
            .' AND `transaction_ref` = "'.pSQL($data['transaction_ref']).'"'
            .' AND `notification_code` = '.(int) $data['notification_code']
            .' AND `status` NOT IN("'.NotificationStatus::SUCCESS.'", "'.NotificationStatus::NOT_HANDLED.'")';

            return Db::getInstance()->update(HipayDBQueryAbstract::HIPAY_NOTIFICATION_TABLE, $safeData, $where);
        }
    }
}
