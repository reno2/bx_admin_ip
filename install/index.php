<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class burns_orderip extends CModule
{

		var $exclusionAdminFiles;

		/**
		 * burns_table constructor.
		 */
		function __construct()
		{
				$arModuleVersion = array();
				include(__DIR__ . "/version.php");

				$this->exclusionAdminFiles = array(
						'..',
						'.',
						'menu.php',
						'operation_description.php',
						'task_description.php'
				);

				$this->MODULE_ID           = 'burns.orderip';
				$this->MODULE_VERSION      = $arModuleVersion["VERSION"];
				$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
				$this->MODULE_NAME         = Loc::getMessage("BURNS_ORDERIP_MODULE_NAME");
				$this->MODULE_DESCRIPTION  = Loc::getMessage("BURNS_ORDERIP_MODULE_DESC");
				$this->PARTNER_NAME        = Loc::getMessage("BURNS_ORDERIP_PARTNER_NAME");
				$this->PARTNER_URI         = Loc::getMessage("BURNS_ORDERIP_PARTNER_URI");
		}

		//Проверяем что система поддерживает D7
		public function isVersionD7()
		{
				return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
		}
		/**
		 * @param bool $notDocumentRoot
		 *
		 * @return string|string[]
		 */
		//Определяем место размещения модуля
		public function GetPath($notDocumentRoot = false)
		{
				if ($notDocumentRoot)
						return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
				else
						return dirname(__DIR__);
		}

		/**
		 *
		 */
		function DoInstall()
		{
				global $APPLICATION;
				if ($this->isVersionD7())
				{
						\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
						Loader::includeModule("burns.orderip");
						$this->InstallEvents();
						$this->InstallDB();
						//$this->InstallFiles();
				}
				else
				{
						$APPLICATION->ThrowException(Loc::getMessage("BURNS_ORDERIP_ERROR_VERSION"));
				}
				$APPLICATION->IncludeAdminFile(Loc::getMessage("BURNS_ORDERIP_INSTALL_TITLE"), $this->GetPath() . "/install/step.php");
		}

		function DoUninstall()
		{
				global $APPLICATION;

				$context = Application::getInstance()->getContext();
				$request = $context->getRequest();

				if ($request["step"] < 2)
				{
						$APPLICATION->IncludeAdminFile(Loc::getMessage("BURNS_ORDERIP_UNINSTALL_TITLE"), $this->GetPath() . "/install/unstep1.php");
				}
				elseif ($request["step"] == 2)
				{
						//$this->UnInstallFiles();
						$this->UnInstallEvents();
						if ($request["savedata"] != "Y")
								$this->UnInstallDB();

						\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
						$APPLICATION->IncludeAdminFile(Loc::getMessage("BURNS_ORDERIP_UNINSTALL_TITLE"), $this->GetPath() . "/install/unstep2.php");
				}
		}

		function InstallFiles($arParams = array())
		{

				$path = $this->GetPath() . "/install/components";
				if (\Bitrix\Main\IO\Directory::isDirectoryExists($path))
						CopyDirFiles($path, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true);
				else
						throw new \Bitrix\Main\IO\InvalidPathException($path);

				if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin'))
				{
						CopyDirFiles($this->GetPath() . "/install/admin/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin"); //если есть файлы для копирования
						if ($dir = opendir($path))
						{
								while (false !== $item = readdir($dir))
								{
										if (in_array($item, $this->exclusionAdminFiles))
												continue;
										file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $this->MODULE_ID . '_' . $item,
												'<' . '? require($_SERVER["DOCUMENT_ROOT"]."' . $this->GetPath(true) . '/admin/' . $item . '");?' . '>');
								}
								closedir($dir);
						}
				}

				return true;
		}

		function UnInstallFiles()
		{
				\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/burns/');

				if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin'))
				{

						DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . $this->GetPath() . '/install/admin/', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');
						if ($dir = opendir($path))
						{
								while (false !== $item = readdir($dir))
								{
										if (in_array($item, $this->exclusionAdminFiles))
												continue;
										\Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $this->MODULE_ID . '_' . $item);
								}
								closedir($dir);
						}
				}

				return false;
		}

		/**
		 * @return bool|void
		 */
		function InstallEvents()
		{
				// Вызывается после создания и расчета обьекта заказа.
				EventManager::getInstance()->registerEventHandler(
						'sale',
						'OnSaleOrderSaved',
						$this->MODULE_ID,
						"Burns\Mainorder",
						'onSaleOrderSaved'
				);


				EventManager::getInstance()->registerEventHandler(
						'sale',
						'onSaleAdminOrderInfoBlockShow',
						$this->MODULE_ID,
						"Burns\Mainorder",
						'show'
				);

				return false;
		}


		function InstallDB()
		{
				Loader::includeModule($this->MODULE_ID);

				CModule::IncludeModule($this->MODULE_ID);
				if (!Application::getConnection(\Burns\OrderipTable::getConnectionName())->isTableExists(
						Base::getInstance('\Burns\OrderipTable')->getDBTableName()
				)
				)
				{
						Base::getInstance('\Burns\OrderipTable')->createDbTable();
				}

				return true;
		}


		public function UnInstallDB()
		{

				Loader::includeModule($this->MODULE_ID);
				// Проверяет от текущего подключения

				Application::getConnection(\Burns\OrderipTable::getConnectionName())->queryExecute('drop table if exists ' . Base::getInstance('\Burns\OrderipTable')->getDBTableName());

				Option::delete($this->MODULE_ID);

				return false;

		}

		function UnInstallEvents()
		{
				EventManager::getInstance()->unRegisterEventHandler(
						'sale',
						'OnSaleOrderSaved',
						$this->MODULE_ID,
						"Burns\Mainorder",
						'onSaleOrderSaved'
				);

				EventManager::getInstance()->unRegisterEventHandler(
						"main",
						"onSaleAdminOrderInfoBlockShow",
						$this->MODULE_ID,
						"Burns\Mainorder",
						"show"
				);


				return false;
		}

}