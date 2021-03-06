<?php
/**
 * Os Studios PagSeguro Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   OsStudios
 * @package    OsStudios_PagSeguro
 * @copyright  Copyright (c) 2012 Os Studios (www.osstudios.com.br)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Tiago Sampaio <tiago.sampaio@osstudios.com.br>
 */

/**
 * PagSeguro Payment Installments Block
 *
 */

class OsStudios_PagSeguro_Block_Installments extends Mage_Core_Block_Template
{
    
    protected $_showScripts = true;
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('osstudios/pagseguro/installments.phtml');
    }
    
    protected function _beforeToHtml()
    {
        $this->_prepareForm();
        return parent::_beforeToHtml();
    }

    /**
     * Returns PagSeguro Singleton Object
     *
     * @return OsStudios_Pagseguro_Model_Payment
     */
    public function getPagSeguro()
    {
        return Mage::getSingleton('pagseguro/hpp');
    }

    /**
     * Returns Product's Final Price.
     *
     * @return float
     */
    public function getProductFinalPrice()
    {
        $price = preg_replace("/^R\\$[ ]*/i", "", $this->getRequest()->getParam('price'));
        $price = str_replace(".", "", $price);
        $price = str_replace(",", ".", $price);
        
        if ($price > 0) {
            $this->_showScripts = false;
        } else {
        
            $productId = $this->getRequest()->getParam('id');
            if (!Mage::registry('product') && $productId) {
                $product = Mage::getModel('catalog/product')->load($productId);
                Mage::register('product', $product);
            } else {
                $product = Mage::registry('product');
            }
            
            if ($product) {
                $price = $product->getFinalPrice();
            } else {
                $price = 0;
            }
            
        }
        
        return $price;
        
    }
    
    protected function _prepareForm()
    {
        $pagseguro 					= $this->getPagSeguro();
        $helper 					= Mage::helper("pagseguro");
        
        $pagseguroEnabled 			= $pagseguro->getConfigData('active');
        $installmentsAdd 			= $pagseguro->getConfigData('installments_add');
        $installmentsUpfront 		= $pagseguro->getConfigData('installments_upfront');
        
        $installments 				= $upfrontPrice = $upfrontDiscount = '';
        $finalValue 				= $this->getProductFinalPrice();
        
        $installmentsShow 			= (boolean) ($pagseguro->getConfigData('installments_show') && $pagseguroEnabled && $finalValue);
        $installmentsUpfrontShow 	= (boolean) ($installmentsUpfront && $installmentsAdd != 0);
        
        if ($installmentsShow) {
            $installments = $helper->calculateInstallments($finalValue, $installmentsAdd);
        
            if ($installmentsUpfrontShow) {
                list($upfrontPrice, $upfrontDiscount) = $helper->calculateUpfrontPrice($finalValue, $installmentsAdd);
            }
        }
        
        $this->addData(array(
            'installments' => $installments,
            'upfront_price' => $upfrontPrice,
            'upfront_discount' => $upfrontDiscount,
            'show_installments_scripts' => (boolean) ($pagseguroEnabled && $this->_showScripts),
            'show_installments' => $installmentsShow,
            'show_upfront_price' => $installmentsUpfrontShow,
        ));
    }
    
}