<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\System\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;

/**
 * Backend model for saving certificate
 */
class PrivateKey extends Encrypted
{
    /**
     * Decode and encrypt value before saving
     *
     * @return void
     */
    public function beforeSave()
    {
        $this->_dataSaveAllowed = false;
        $value = (string)$this->getValue();
        // don't save value, if an obscured value was received. This indicates that data was not changed.
        if (!preg_match('/^\*+$/', $value) && !empty($value)) {
            $this->_dataSaveAllowed = true;
            $decoded = base64_decode($value, true);
            if (!$decoded || @base64_encode($decoded) !== $value) {
                $decoded = '';
            }
            $encrypted = $decoded ? $this->_encryptor->encrypt($decoded) : null;
            $this->setValue($encrypted);
        } elseif (empty($value)) {
            $this->setValue(null);
            $this->_dataSaveAllowed = true;
        }
    }
}
