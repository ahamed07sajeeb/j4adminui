<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Menus\Administrator\View\Items;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;

/**
 * The HTML Menus Menu Items View.
 *
 * @since  1.6
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Array used for displaying the levels filter
	 *
	 * @var    \stdClass[]
	 * @since  4.0.0
	 */
	protected $f_levels;

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
	 * Ordering of the items
	 *
	 * @var    array
	 * @since  4.0.0
	 */
	protected $ordering;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function display($tpl = null)
	{
		$lang = Factory::getLanguage();
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->total         = $this->get('Total');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			MenusHelper::addSubmenu('items');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->ordering = array();

		// Preprocess the list of items to find ordering divisions.
		foreach ($this->items as $item)
		{
			$this->ordering[$item->parent_id][] = $item->id;

			// Item type text
			switch ($item->type)
			{
				case 'url':
					$value = Text::_('COM_MENUS_TYPE_EXTERNAL_URL');
					break;

				case 'alias':
					$value = Text::_('COM_MENUS_TYPE_ALIAS');
					break;

				case 'separator':
					$value = Text::_('COM_MENUS_TYPE_SEPARATOR');
					break;

				case 'heading':
					$value = Text::_('COM_MENUS_TYPE_HEADING');
					break;

				case 'container':
					$value = Text::_('COM_MENUS_TYPE_CONTAINER');
					break;

				case 'component':
				default:
					// Load language
						$lang->load($item->componentname . '.sys', JPATH_ADMINISTRATOR, null, false, true)
					|| $lang->load($item->componentname . '.sys', JPATH_ADMINISTRATOR . '/components/' . $item->componentname, null, false, true);

					if (!empty($item->componentname))
					{
						$titleParts   = array();
						$titleParts[] = Text::_($item->componentname);
						$vars         = null;

						parse_str($item->link, $vars);

						if (isset($vars['view']))
						{
							// Attempt to load the view xml file.
							$file = JPATH_SITE . '/components/' . $item->componentname . '/views/' . $vars['view'] . '/metadata.xml';

							if (!is_file($file))
							{
								$file = JPATH_SITE . '/components/' . $item->componentname . '/view/' . $vars['view'] . '/metadata.xml';
							}

							if (is_file($file) && $xml = simplexml_load_file($file))
							{
								// Look for the first view node off of the root node.
								if ($view = $xml->xpath('view[1]'))
								{
									// Add view title if present.
									if (!empty($view[0]['title']))
									{
										$viewTitle = trim((string) $view[0]['title']);

										// Check if the key is valid. Needed due to B/C so we don't show untranslated keys. This check should be removed with Joomla 4.
										if ($lang->hasKey($viewTitle))
										{
											$titleParts[] = Text::_($viewTitle);
										}
									}
								}
							}

							$vars['layout'] = $vars['layout'] ?? 'default';

							// Attempt to load the layout xml file.
							// If Alternative Menu Item, get template folder for layout file
							if (strpos($vars['layout'], ':') > 0)
							{
								// Use template folder for layout file
								$temp = explode(':', $vars['layout']);
								$file = JPATH_SITE . '/templates/' . $temp[0] . '/html/' . $item->componentname . '/' . $vars['view'] . '/' . $temp[1] . '.xml';

								// Load template language file
								$lang->load('tpl_' . $temp[0] . '.sys', JPATH_SITE, null, false, true)
								||	$lang->load('tpl_' . $temp[0] . '.sys', JPATH_SITE . '/templates/' . $temp[0], null, false, true);
							}
							else
							{
								// Get XML file from component folder for standard layouts
								$file = JPATH_SITE . '/components/' . $item->componentname . '/tmpl/' . $vars['view']
									. '/' . $vars['layout'] . '.xml';

								if (!file_exists($file))
								{
									$file = JPATH_SITE . '/components/' . $item->componentname . '/views/'
										. $vars['view'] . '/tmpl/' . $vars['layout'] . '.xml';

									if (!file_exists($file))
									{
										$file = JPATH_SITE . '/components/' . $item->componentname . '/view/'
											. $vars['view'] . '/tmpl/' . $vars['layout'] . '.xml';
									}
								}
							}

							if (is_file($file) && $xml = simplexml_load_file($file))
							{
								// Look for the first view node off of the root node.
								if ($layout = $xml->xpath('layout[1]'))
								{
									if (!empty($layout[0]['title']))
									{
										$titleParts[] = Text::_(trim((string) $layout[0]['title']));
									}
								}

								if (!empty($layout[0]->message[0]))
								{
									$item->item_type_desc = Text::_(trim((string) $layout[0]->message[0]));
								}
							}

							unset($xml);

							// Special case if neither a view nor layout title is found
							if (count($titleParts) == 1)
							{
								$titleParts[] = $vars['view'];
							}
						}

						$value = implode(' » ', $titleParts);
					}
					else
					{
						if (preg_match("/^index.php\?option=([a-zA-Z\-0-9_]*)/", $item->link, $result))
						{
							$value = Text::sprintf('COM_MENUS_TYPE_UNEXISTING', $result[1]);
						}
						else
						{
							$value = Text::_('COM_MENUS_TYPE_UNKNOWN');
						}
					}
					break;
			}

			$item->item_type = $value;
			$item->protected = $item->menutype == 'main';
		}

		// Levels filter.
		$options   = array();
		$options[] = HTMLHelper::_('select.option', '1', Text::_('J1'));
		$options[] = HTMLHelper::_('select.option', '2', Text::_('J2'));
		$options[] = HTMLHelper::_('select.option', '3', Text::_('J3'));
		$options[] = HTMLHelper::_('select.option', '4', Text::_('J4'));
		$options[] = HTMLHelper::_('select.option', '5', Text::_('J5'));
		$options[] = HTMLHelper::_('select.option', '6', Text::_('J6'));
		$options[] = HTMLHelper::_('select.option', '7', Text::_('J7'));
		$options[] = HTMLHelper::_('select.option', '8', Text::_('J8'));
		$options[] = HTMLHelper::_('select.option', '9', Text::_('J9'));
		$options[] = HTMLHelper::_('select.option', '10', Text::_('J10'));

		$this->f_levels = $options;

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();

			// We do not need to filter by language when multilingual is disabled
			if (!Multilanguage::isEnabled())
			{
				unset($this->activeFilters['language']);
				$this->filterForm->removeField('language', 'filter');
			}
		}
		else
		{
			// In menu associations modal we need to remove language filter if forcing a language.
			if ($forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'CMD'))
			{
				// If the language is forced we can't allow to select the language, so transform the language selector filter into a hidden field.
				$languageXml = new \SimpleXMLElement('<field name="language" type="hidden" default="' . $forcedLanguage . '" />');
				$this->filterForm->setField($languageXml, 'filter', true);

				// Also, unset the active language filter so the search tools is not open by default with this filter.
				unset($this->activeFilters['language']);
			}
		}

		// Allow a system plugin to insert dynamic menu types to the list shown in menus:
		Factory::getApplication()->triggerEvent('onBeforeRenderMenuItems', array($this));

		parent::display($tpl);
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
		$menutypeId = (int) $this->state->get('menutypeid');

		$canDo = ContentHelper::getActions('com_menus', 'menu', (int) $menutypeId);
		$user  = Factory::getUser();

		// Get the menu title
		$menuTypeTitle = $this->get('State')->get('menutypetitle');

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		// toolbar title
		if ($menuTypeTitle)
		{
			ToolbarHelper::title(Text::sprintf('COM_MENUS_VIEW_ITEMS_MENU_TITLE', $menuTypeTitle), 'menu menumgr');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_MENUS_VIEW_ITEMS_ALL_TITLE'), 'menu menumgr');
		}

		// menutype filter
		$protected = $this->state->get('filter.menutype') == 'main';

		// action group toolbar dropdown
		if (($canDo->get('core.edit.state') || Factory::getUser()->authorise('core.admin')) && !$protected
			|| $canDo->get('core.edit.state') && $this->state->get('filter.client_id') == 0)
		{
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_SELECT_ACTION')
				->toggleSplit(false)
				->icon('icon-select')
				->buttonClass('btn btn-white')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			if ($canDo->get('core.edit.state') && !$protected)
			{
				$childBar->publish('items.publish')->listCheck(true);

				$childBar->unpublish('items.unpublish')->listCheck(true);
			}

			if (Factory::getUser()->authorise('core.admin') && !$protected)
			{
				$childBar->checkin('articles.checkin')->listCheck(true);
			}

			if ($canDo->get('core.edit.state') && $this->state->get('filter.published') != -2)
			{
				if ($this->state->get('filter.client_id') == 0)
				{
					$childBar->makeDefault('items.setDefault')->listCheck(true);
				}

				if (!$protected)
				{
					$childBar->trash('items.trash')->listCheck(true);
				}
			}

			// Add a batch button
			if (!$protected && $user->authorise('core.create', 'com_menus')
				&& $user->authorise('core.edit', 'com_menus')
				&& $user->authorise('core.edit.state', 'com_menus'))
			{
				$childBar->popupButton('batch')
					->text('JTOOLBAR_BATCH')
					->selector('collapseModal')
					->listCheck(true);
			}
		}

		// toolbar rebuild menuItem tree button
		if (Factory::getUser()->authorise('core.admin'))
		{
			$toolbar->standardButton('refresh')
				->text('JTOOLBAR_REBUILD')
				->task('items.rebuild');
		}

		// toolbar delete menuItem button
		if (!$protected && $this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			$toolbar->delete('items.delete')
				->text('JTOOLBAR_EMPTY_TRASH')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		}

		// toolbar help button
		$toolbar->help('JHELP_MENUS_MENU_ITEM_MANAGER');
		
		// option toolbar button
		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			$toolbar->preferences('com_menus');
		}

		// toolbar addNew button
		if ($canDo->get('core.create'))
		{
			$toolbar->addNew('item.add');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		$this->state = $this->get('State');

		if ($this->state->get('filter.client_id') == 0)
		{
			return array(
				'a.lft'       => Text::_('JGRID_HEADING_ORDERING'),
				'a.published' => Text::_('JSTATUS'),
				'a.title'     => Text::_('JGLOBAL_TITLE'),
				'a.home'      => Text::_('COM_MENUS_HEADING_HOME'),
				'a.access'    => Text::_('JGRID_HEADING_ACCESS'),
				'association' => Text::_('COM_MENUS_HEADING_ASSOCIATION'),
				'language'    => Text::_('JGRID_HEADING_LANGUAGE'),
				'a.id'        => Text::_('JGRID_HEADING_ID')
			);
		}
		else
		{
			return array(
				'a.lft'       => Text::_('JGRID_HEADING_ORDERING'),
				'a.published' => Text::_('JSTATUS'),
				'a.title'     => Text::_('JGLOBAL_TITLE'),
				'a.id'        => Text::_('JGRID_HEADING_ID')
			);
		}
	}
}
