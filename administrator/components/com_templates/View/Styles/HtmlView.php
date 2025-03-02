<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_templates
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Templates\Administrator\View\Styles;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of template styles.
 *
 * @since  1.6
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  \Joomla\CMS\Pagination\Pagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  \JObject
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var    \JForm
	 * @since  4.0.0
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  4.0.0
	 */
	public $activeFilters;

	/**
	 * Is the parameter enabled to show template positions in the frontend?
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	public $preview;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->total         = $this->get('Total');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->preview       = ComponentHelper::getParams('com_templates')->get('template_positions_display');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_templates');

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');
		
		// Set the title.
		if ((int) $this->get('State')->get('client_id') === 1)
		{
			ToolbarHelper::title(Text::_('COM_TEMPLATES_MANAGER_STYLES_ADMIN'), 'brush thememanager');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_TEMPLATES_MANAGER_STYLES_SITE'), 'brush thememanager');
		}

		// batch actions
		if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit.state'))
		{
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_SELECT_ACTION')
				->toggleSplit(false)
				->icon('icon-select')
				->buttonClass('btn btn-white')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			if ($canDo->get('core.edit.state'))
			{
				$childBar->standardButton('star')
					->text('COM_TEMPLATES_TOOLBAR_SET_HOME')
					->task('styles.setDefault')
					->listCheck(true);
			}

			if ($canDo->get('core.create'))
			{
				$childBar->standardButton('copy')
					->text('JTOOLBAR_DUPLICATE')
					->task('styles.duplicate')
					->listCheck(true);
			}

			if ($canDo->get('core.delete'))
			{
				$childBar->delete('styles.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);
			}
		}

		// Install new template
		ToolbarHelper::modal('ModalInstallTemplate', 'icon-arrow-down-2', 'JTOOLBAR_INSTALL_TEMPLATE');

		ToolbarHelper::help('JHELP_EXTENSIONS_TEMPLATE_MANAGER_STYLES');

		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			ToolbarHelper::preferences('com_templates');
		}
	}
}
