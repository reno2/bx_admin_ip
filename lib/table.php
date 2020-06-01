<?php
/**
 * Created by PhpStorm.
 * User: reno
 * Date: 07.11.2019
 * Time: 16:03
 */

namespace Burns;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;


class OrderipTable extends Entity\DataManager
{
		public static function getTableName()
		{
				return 'burns_table';
		}


		public static function getMap()
		{
				return array(
						//ID
						new Entity\IntegerField('ID', array(
								'primary'      => true,
								'autocomplete' => true
						)),
						//ORDER_ID
						new Entity\IntegerField('ORDER_ID'),
						//Название
						new Entity\StringField('CITY', array(
								'required' => true,
						)),
						//Название
						new Entity\StringField('IP', array(
								'required' => true,
						)),


				);
		}
}
