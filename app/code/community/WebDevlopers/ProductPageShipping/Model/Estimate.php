<?php



class WebDevlopers_ProductPageShipping_Model_Estimate
{
   
    protected $_customer = null;

    
    protected $_quote = null;

    
    protected $_product = null;

    
    protected $_result = array();

    
    protected $_addressInfo = null;
   
     protected $_tamanho = null;

   public function setTamanho($tamanho){
      $this->_tamanho = $tamanho;
      return $this->_tamanho;
   }
   public function getTamanho(){
        return $this->_tamanho;
   }
   
    public function setAddressInfo($info)
    {
        $this->_addressInfo = $info;
        return $this;
    }

    
    public function getAddressInfo()
    {
        return $this->_addressInfo;
    }

    
    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }

    
    public function getProduct()
    {
       $prod = $this->_product;
       //Verify if the product is configurable, since configurable products doesn’t have weight to estimate
            if($this->_product->isConfigurable()){
                //For convenience, creates a new variable just for our product
                $configurableProduct = $this->_product;
                //Load an array with all the associated products
                $associated_products = $configurableProduct->loadByAttribute('sku', $configurableProduct->getSku())->getTypeInstance()->getUsedProducts();
                //Run foreach just once to get the first of the associated products
               
                foreach($associated_products as $assoc){
                     $logss = $this->getTamanho();
                                       
                     if (!empty($logss[203])){
                        
                        if ($assoc->getTamanho() == $logss[203]){
                            $prod = Mage::getModel('catalog/product')->load($assoc->getId());
                            //essa alteração é unica para a loja pois pega tamanho do atributo tamanho onde o peso é maior
                            break;
                        } 
                      
                     } else {
                          $prod = Mage::getModel('catalog/product')->load($assoc->getId());                   
                          
                     }
                 }
             }
            //$logss = $prod;
            //Mage::log($prod);
            $this->_product = $prod;
            return $prod;
       
        //return $this->_product;
    }

    
    public function getResult()
    {
        return $this->_result;
    }

    
    public function estimate()
    {
        $product = $this->getProduct();
        $addToCartInfo = (array) $product->getAddToCartInfo();
        $addressInfo = (array) $this->getAddressInfo();


        if (!($product instanceof Mage_Catalog_Model_Product) || !$product->getId()) {
            Mage::throwException(
                Mage::helper('webdevlopers_productpageshipping')->__('Please specify a valid product')
            );
        }

        if (!isset($addressInfo['country_id'])) {
            Mage::throwException(
                Mage::helper('webdevlopers_productpageshipping')->__('Please specify a country')
            );
        }

        if (empty($addressInfo['cart'])) {
            $this->resetQuote();
        }

        $shippingAddress = $this->getQuote()->getShippingAddress();

        //$shippingAddress->setCountryId($addressInfo['country_id']);

        if (isset($addressInfo['region_id'])) {
           // $shippingAddress->setRegionId($addressInfo['region_id']);
        }

        if (isset($addressInfo['postcode'])) {
           // $shippingAddress->setPostcode($addressInfo['postcode']);
        }

        if (isset($addressInfo['region'])) {
           // $shippingAddress->setRegion($addressInfo['region']);
        }

        if (isset($addressInfo['city'])) {
           // $shippingAddress->setCity($addressInfo['city']);
        }

       // $shippingAddress->setCollectShippingRates(true);

        if (isset($addressInfo['coupon_code'])) {
            $this->getQuote()->setCouponCode($addressInfo['coupon_code']);
        }

        $request = new Varien_Object($addToCartInfo);

        if ($product->getStockItem()) {
            $minimumQty = $product->getStockItem()->getMinSaleQty();
            if($minimumQty > 0 && $request->getQty() < $minimumQty){
                $request->setQty($minimumQty);
            }
        }

        $result = $this->getQuote()->addProduct($product, $request);

        if (is_string($result)) {
            Mage::throwException($result);
        }

        Mage::dispatchEvent('checkout_cart_product_add_after',
                            array('quote_item' => $result, 'product' => $product));

        $this->getQuote()->collectTotals();
        $this->_result = $shippingAddress->getGroupedAllShippingRates();
        return $this;
    }

    
    public function getQuote()
    {
        if ($this->_quote === null) {
            $addressInfo = $this->getAddressInfo();
            if (!empty($addressInfo['cart'])) {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
            } else {
                $quote = Mage::getModel('sales/quote');
            }

            $this->_quote = $quote;
        }

        return $this->_quote;
    }

    
    public function resetQuote()
    {
        $this->getQuote()->removeAllAddresses();

        if ($this->getCustomer()) {
            $this->getQuote()->setCustomer($this->getCustomer());
        }

        return $this;
    }

    
    public function getCustomer()
    {
        if ($this->_customer === null) {
            $customerSession = Mage::getSingleton('customer/session');
            if ($customerSession->isLoggedIn()) {
                $this->_customer = $customerSession->getCustomer();
            } else {
                $this->_customer = false;
            }
        }

        return $this->_customer;
    }
}
