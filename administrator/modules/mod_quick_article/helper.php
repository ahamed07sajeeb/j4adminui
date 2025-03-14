<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_quick_article
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\String\StringHelper;

/**
 * mod_quick_article_helper helper class for the module
 * 
 */
class ModQuickArticleHelper
{
    /**
     * Save quick article by http request
     * 
     * @return {json}   response message
     * 
     * @since   1.0.0
     */
    public static function saveQuickArticleAjax()
    {
        $app = Factory::getApplication();
        $table = $app->bootComponent('com_content')->getMVCFactory()->createTable('Article', 'Administrator', ['ignore_request' => true]);
        $user = Factory::getUser();
        
        // Check if the user has the access permission to create a new article
        if (!$user->authorise('core.create', 'com_content')) 
        {
            $app->setHeader('status', 401, true);
            $app->sendHeaders();
            echo Text::_('MOD_QUICK_ARTICLE_NOT_AUTHORISED');
            $app->close();
            
        }
        
        $input = $app->input;
        
        // read input 
        $title = $input->post->get('title', '', 'STRING');
        $description = $input->post->get('description', '', 'STRING');
        $catid = $input->post->get('catid', 2, 'INT'); // default is 2 is for uncategorised category for article
        
        $table->load();
        $table->title       = $title;
        $table->alias       = static::generateSafeAlias($title, $catid);
        $table->catid       = $catid;
        $table->introtext   = "<p>" . $description . "</p>";
        $table->fulltext    = "";
        $table->images      = "";
        $table->urls        = "";
        $table->attribs     = "";
        $table->metakey     = "";
        $table->metadesc    = "";
        $table->metadata    = "";
        $table->state       = 1;
        $table->created     = Factory::getDate()->toSql();
        $table->created_by  = $user->id;
        $table->language    = '*';
        
        $articleid = 0;

        try 
        {
            if ($table->store(true))
            {
                $articleid = $table->id;
                if (!static::workflowAssociationsPivot($articleid))
                {
                    $app->setHeader('status', 500, true);
                    $app->sendHeaders();
                    echo Text::_("MOD_QUICK_ARTICLE_CANNOT_SAVE_WORKFLOW");
                    $app->close();
                }
            }
            else 
            {
                $app->setHeader('status', 500, true);
                $app->sendHeaders();
                echo Text::_('MOD_QUICK_ARTICLE_CANNOT_SAVE_INTO_DB');
                $app->close();
            }
        }
        catch (Exception $e)
        {
            $app->setHeader('status', 500, true);
            $app->sendHeaders();
            echo $e->getMessage();
            $app->close();
        }
        
        $app->setHeader('status', 200, true);
        $app->sendHeaders();
        echo new JsonResponse($articleid);
        $app->close();
    }

    /**
     * generate safe alias from title
     * 
     * @param {string} $title - the title from which the alias will be generated
     * @param {int} $catid      - the category id. The alias could be same for different catid
     * 
     * @return {string} $alias
     * 
     * @since 1.0.0
     */
    private static function generateSafeAlias($title, $catid)
    {
        $app = Factory::getApplication();
        $articleTable = $app->bootComponent('com_content')->getMVCFactory()->createTable('Article', 'Administrator', ['ignore_request' => true]);
        $alias = ApplicationHelper::stringURLSafe($title);

        while ($articleTable->load(array('alias' => $alias, 'catid' => $catid)))
        {
            $alias = StringHelper::increment($alias, 'dash');
        }

        return $alias;
    }

    /**
     * store a record into #__workflow_associations pivot table
     * 
     * @param {int} $item_id - newly created article id
     * 
     * @return {boolean}
     * 
     * @since 1.0.0
     */
    private static function workflowAssociationsPivot($item_id)
    {
        $db     = Factory::getDbo();
        $query  = $db->getQuery(true);

        $columns    = array('item_id', 'stage_id', 'extension');
        $values     = array($item_id, 1, $db->quote('com_content'));
        
        try 
        {
            $query->insert($db->quoteName('#__workflow_associations'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

            $db->setQuery($query);
            $db->execute();
        }
        catch (Exception $e)
        {
            return false;
        }
        return true;
    }

    /**
     * get categories for the component com_content
     * 
     * @return {string} - The categories select option generated by select.genericlist
     * 
     * @since 1.0.0
     */
    public static function getCategories() : string
    {
        $categories = array();

        // Get categories model
        $categoryModel = Factory::getApplication()->bootComponent('com_categories')
            ->getMVCFactory()->createModel('Categories', 'Administrator', ['ignore_request' => true]);
        
        // Set State filter.extension = com_content; this will get the categories for com_content
        $categoryModel->setState('filter.extension', 'com_content');
        
        // Get the categories items
        $categories = $categoryModel->getItems();

        /**
         * Generate the options for categories select field
         * If there is a level greated than 1 then append (-) with the category title
         */
        $options = array();

        if (!empty($categories))
        {
            foreach($categories as $category)
            {
                $title = $category->title;
                
                if ($category->level > 1)
                {
                    $title = str_repeat(' - ', $category->level) . $title;
                }
                
                $options[] = HTMLHelper::_('select.option', $title, $category->id, 'text', 'value');
            }
        }

        // Add select attributes. The category field is required
        $attributes = array();
        $attributes[] = "required='required'";
        
        // Return the categories select
        return HTMLHelper::_('select.genericlist', $options, 'catid', implode($attributes));
    }
}