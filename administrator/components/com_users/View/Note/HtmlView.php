<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\View\Note;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * User note edit view
 *
 * @since  2.5
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The edit form.
	 *
	 * @var    \JForm
	 * @since  2.5
	 */
	protected $form;

	/**
	 * The item data.
	 *
	 * @var    object
	 * @since  2.5
	 */
	protected $item;

	/**
	 * The model state.
	 *
	 * @var    CMSObject
	 * @since  2.5
	 */
	protected $state;

	/**
	 * Override the display method for the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception
	 */
	public function display($tpl = null)
	{
		// Initialise view variables.
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		parent::display($tpl);
		$this->addToolbar();
	}

	/**
	 * Display the toolbar.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception
	 */
	protected function addToolbar()
	{
		$input = Factory::getApplication()->input;
		$input->set('hidemainmenu', 1);

		$user       = Factory::getUser();
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

		// Since we don't track these assets at the item level, use the category id.
		$canDo = ContentHelper::getActions('com_users', 'category', $this->item->catid);

		$toolbar = Toolbar::getInstance();

		ToolbarHelper::title(Text::_('COM_USERS_NOTES'), 'user-notes user');

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_USERS_USER_NOTES_EDIT');

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('note.cancel');
		}
		else
		{
			if (ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get('save_history', 0) && $canDo->get('core.edit'))
			{
				ToolbarHelper::versions('com_users.note', $this->item->id);
			}

			ToolbarHelper::cancel('note.cancel', 'JTOOLBAR_CLOSE');
		}

		// Save item group 
		$saveGroup = $toolbar->dropdownButton('save-group');

		$saveGroup->configure(
			function (Toolbar $childBar) use ($checkedOut, $canDo, $user, $isNew)
			{
				// If not checked out, can save the item.
				if (!$checkedOut && ($canDo->get('core.edit') || count($user->getAuthorisedCategories('com_users', 'core.create'))))
				{
					$childBar->apply('note.apply');
					$childBar->save('note.save');
				}

				if (!$checkedOut && count($user->getAuthorisedCategories('com_users', 'core.create')))
				{
					$childBar->save2new('note.save2new');
				}

				// If an existing item, can save to a copy.
				if (!$isNew && (count($user->getAuthorisedCategories('com_users', 'core.create')) > 0))
				{
					$childBar->save2copy('note.save2copy');
				}

			}
		);
	}
}
