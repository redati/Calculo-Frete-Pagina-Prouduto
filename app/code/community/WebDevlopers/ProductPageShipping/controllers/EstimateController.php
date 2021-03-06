<?php


require_once 'app/code/core/Mage/Catalog/controllers/ProductController.php';


class WebDevlopers_ProductPageShipping_EstimateController extends Mage_Catalog_ProductController
{
    
    public function estimateAction()
    {
        $product = $this->_initProduct();
        $this->loadLayout(false);
        $block = $this->getLayout()->getBlock('shipping.estimate.result');
        if ($block) {
            $estimate = $block->getEstimate();
            $product->setAddToCartInfo((array) $this->getRequest()->getPost());
            $estimate->setProduct($product);
            $addressInfo = $this->getRequest()->getPost('estimate');
            $tamanho = $this->getRequest()->getPost('super_attribute');
           
            $estimate->setAddressInfo((array) $addressInfo);
            $estimate->setTamanho((array) $tamanho);
            
            $block->getSession()->setFormValues($addressInfo);
            try {
                $estimate->estimate();
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('catalog/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('catalog/session')->addError(
                    Mage::helper('webdevlopers_productpageshipping')->__('There was an error during processing your shipping request')
                );
            }
        }
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }
}
