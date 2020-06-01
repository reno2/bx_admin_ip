<?php
namespace Burns;
use \Bitrix\Main\Loader;

use \Bitrix\Main\Entity\Event;
use \Bitrix\Sale;
use \Bitrix\Main;
use \Burns\OrderipTable;



class Mainorder{

		static function ormGet($id){
				$result = \Burns\OrderipTable::getList(array(
						'filter'=>array('ORDER_ID'=> (int)$id)
				))->fetch();
				if(count($result))
						return $result;
				else
						return false;
		}

		function show(\Bitrix\Main\Event $event)
		{
				$order = $event->getParameter("ORDER")->getId();
				$value = self::ormGet($order);
				if($value)
				{
						return new \Bitrix\Main\EventResult(
								\Bitrix\Main\EventResult::SUCCESS,
								array(
										array('TITLE' => 'City Data:',
										      'VALUE' => $value['CITY']),
										array('TITLE' => 'IP:',
										      'VALUE' => $value['IP']),
								),
								'sale'
						);
				}
		}



		public static $ip;
		public static $url = 'https://rest.db.ripe.net/search.json?query-string=';

		public static function getData(){

				$result = self::$url.self::$ip;

				$res = file_get_contents($result, null, stream_context_create(['http' => ['method' => 'GET']]));
				if(!empty($http_response_header[0])){
						if($http_response_header[0] == 'HTTP/1.1 200 OK'){
								$r = json_decode($res);
								$m= $r->objects->object;
								$str = '';
								$rrr = $m[0]->attributes;
								if(!empty($m[0]->attributes->attribute))
								{
										foreach ($m[0]->attributes->attribute as $el => $key)
										{
												if($key->name == 'descr'){
														$str = $key->value;
												}
										}
								}
								return $str;
						}
				}

				if($res)
						return $result;
				return false;
		}


		public static function addRow($orderId){
				$s = self::getData();
				$t = '';
				OrderipTable::add(array(
						'ORDER_ID' => $orderId,
						'CITY' =>  $s,
						'IP'   => self::$ip
				));
				$trt = '';
		}


		public function onSaleOrderSaved(Main\Event $event)
		{
				if(!$event->getParameter('IS_NEW'))
						return;
				$parameters = $event->getParameters();
				$order = $event->getParameter('ENTITY');
				self::$ip = $_SERVER['REMOTE_ADDR'];
				if($order instanceof Sale\Order)
				{
						$orderId = $order->getId();
						self::addRow($orderId);
				}
		}

}