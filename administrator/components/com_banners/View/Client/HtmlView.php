<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Banners\Administrator\View\Client;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Banners\Administrator\Model\ClientModel;

/**
 * View to edit a client.
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The Form object
	 *
	 * @var    Form
	 * @since  1.5
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var    CMSObject
	 * @since  1.5
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var    CMSObject
	 * @since  1.5
	 */
	protected $state;

	/**
	 * Object containing permissions for the item
	 *
	 * @var    CMSObject
	 * @since  1.5
	 */
	protected $canDo;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 *
	 * @throws  Exception
	 */
	public function display($tpl = null): void
	{
		/** @var ClientModel $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();
		$this->canDo = ContentHelper::getActions('com_banners');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 *
	 * @throws  Exception
	 */
	protected function addToolbar(): void
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user       = Factory::getUser();
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->id);
		$canDo      = $this->canDo;

		ToolbarHelper::title(
			$isNew ? Text::_('COM_BANNERS_MANAGER_CLIENT_NEW') : Text::_('COM_BANNERS_MANAGER_CLIENT_EDIT'),
			'bookmark banners-clients'
		);

		if (!empty($this->item->id) && ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get('save_history', 0) && $canDo->get('core.edit'))
		{
			ToolbarHelper::versions('com_banners.client', $this->item->id);
		}

		$toolbarButtons = [];

		// help button
		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_BANNERS_CLIENTS_EDIT');

		// close/cancel button
		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('client.cancel');
		}
		else
		{
			ToolbarHelper::cancel('client.cancel', 'JTOOLBAR_CLOSE');
		}

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create')))
		{	
			$toolbarButtons[] = ['apply', 'client.apply'];
			$toolbarButtons[] = ['save', 'client.save'];
		}

		if (!$checkedOut && $canDo->get('core.create'))
		{
			$toolbarButtons[] = ['save2new', 'client.save2new'];
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			$toolbarButtons[] = ['save2copy', 'client.save2copy'];
		}

		ToolbarHelper::saveGroup(
			$toolbarButtons,
			'btn-success'
		);
	}
}
