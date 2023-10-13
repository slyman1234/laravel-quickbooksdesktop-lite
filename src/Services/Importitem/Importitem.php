<?php

namespace Sylvester\Quickbooks\Services\Importitem;

use Sylvester\Quickbooks\Services\GetRun;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Importitem
{
 

    protected $config;
    protected $QBD;

    public function __construct()
    {  
        $this->config = config('quickbooks');


        $this->QBD  = new GetRun;
    	

    	
    }
	/**
	 * Issue a request to QuickBooks to add a customer
	 */
	public static function xmlRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		// Do something here to load data using your model
		$getrun = new GetRun();

		$config = config('quickbooks');

        $attr_iteratorID = '';
        $attr_iterator = ' iterator="Start" ';
        if (empty($extra['iteratorID']))
        {
            // This is the first request in a new batch

			
            $last = $getrun->GetLastRun($user, $action);
            $getrun->SetLastRun($user, $action);			// Update the last run time to NOW()
            
            // Set the current run to $last
            $getrun->SetCurrentRun($user, $action, $last);
        }
        else
        {
            // This is a continuation of a batch
            $attr_iteratorID = ' iteratorID="' . $extra['iteratorID'] . '" ';
            $attr_iterator = ' iterator="Continue" ';
            
            $last = $getrun->GetCurrentRun($user, $action);
        }
        
        // Build the request
        $xml = '<?xml version="1.0" encoding="utf-8"?>
            <?qbxml version="' . $version . '"?>
            <QBXML>
                <QBXMLMsgsRq onError="stopOnError">
    
                    <ItemQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
                        <MaxReturned>' . $config['QB_QUICKBOOKS_MAX_RETURNED'] . '</MaxReturned>
                        
                 
                        <OwnerID>0</OwnerID>
    
        
                    </ItemQueryRq>	
                </QBXMLMsgsRq>
            </QBXML>';
            
        return $xml;
	}

	/**
	 * Handle a response from QuickBooks indicating a new customer has been added
	 */	
	public static function xmlResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
	
		$config = config('quickbooks');

		if(!$config['qb_dsn']){
			$dbconf 	= config('database');
			$db 	=  $dbconf['connections'][$dbconf['default']];
			if($db['driver'] == 'mysql'){
				$db['driver'] = 'mysqli';
			}
			$dsn = $db['driver'] . '://' . $db['username'] . ':' .$db['password'] . '@' . $db['host'] . ':' . $db['port'] .'/'. $db['database'];
		}
	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = \QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_ITEM, null,0, array( 'iteratorID' => $idents['iteratorID'] ), $user);
	}
	


	  // Perform a category query to retrieve category information
	//   $categoryQueryXML = '<QBXML>...'; // Construct the XML query for categories
	//   $categoryResponseXML = self::sendQueryToQuickBooks($categoryQueryXML); // Use 'self::' to call the function
	//   $categories = self::parseCategoryResponse($categoryResponseXML); // Use 'self::' to call the function
  
	  // Import all of the records
	$errnum = 0;
	$errmsg = '';
	$Parser = new \QuickBooks_XML_Parser($xml);
	if ($Doc = $Parser->parse($errnum, $errmsg))
	{
		$Root = $Doc->getRoot();
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ItemQueryRs');
		
		foreach ($List->children() as $Item)
		{
			$type = substr(substr($Item->name(), 0, -3), 4);
			$ret = $Item->name();
			
		
			$arr = array(
				'listidentity' => $Item->getChildDataAt($ret . ' ListID'),
				'created_at' => $Item->getChildDataAt($ret . ' TimeCreated'),
				'updated_at' => $Item->getChildDataAt($ret . ' TimeModified'),
				'itemname' => $Item->getChildDataAt($ret . ' Name'),
				// 'identifier' => mt_rand(),
				// 'specification' => $Item->getChildDataAt($ret . ' specification'),
				'itemcount' =>  $Item->getChildDataAt($ret . ' QuantityOnHand')
                 
				);
			
			$look_for = array(
				'itemprice' => array( 'SalesOrPurchase Price', 'SalesAndPurchase SalesPrice', 'SalesPrice' ),
				'itemdescription' => array( 'SalesOrPurchase Desc', 'SalesAndPurchase SalesDesc', 'SalesDesc' ),

			); 


			
			foreach ($look_for as $field => $look_here)
			{
				if (!empty($arr[$field]))
				{
					break;
				}
				
				foreach ($look_here as $look)
				{
					$arr[$field] = $Item->getChildDataAt($ret . ' ' . $look);
				}
			}
			
			\QuickBooks_Utilities::log($dsn, 'Importing ' . $type . ' Item ' . $arr['itemname'] . ': ' . print_r($arr, true));
			
			foreach ($arr as $key => $value) {
				$arr[$key] = $value;
			}
			

			$listidentity = $arr['listidentity'];
            $quantity = $arr['itemcount'];
			// $discountamount = $arr['discount_amount'];

			  // Replace 'your_category_field' with the actual field where you store the category in your database
		
			  $category = $Item->getChildDataAt($ret . ' category');
			//   $category = $this->fetchCategoryByListID($listidentity);
			  // Extend your $arr array with the category
			$arr['category'] = $category;
			DB::table('products')->insertOrIgnore($arr);
			
			DB::table('products')
            ->where('listidentity', '=', $listidentity)
            ->update(
			['itemcount' => $quantity]);

			
		//	Log::info(print_r(array($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents), true));
		  
			//trigger_error(print_r(array_keys($arr), true));
			


			
	
	}
	

        return true; 
	}
}
}