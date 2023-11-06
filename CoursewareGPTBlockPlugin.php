<?php

require_once 'lib/gpt_common.inc.php';

StudipAutoloader::addClassLookups([
    'CoursewareGPTBlock\GPTBlock'  => __DIR__ . '/lib/GPTBlock.php',
    'CoursewareGPTBlock\GPTFeedback'  => __DIR__ . '/models/GPTFeedback.php',
    'CoursewareGPTBlock\GPTQuestion'  => __DIR__ . '/models/GPTQuestion.php',
    'CoursewareGPTBlock\GPTUserAnswer'  => __DIR__ . '/models/GPTUserAnswer.php',
    'CoursewareGPTBlock\GPTUserFeedback'  => __DIR__ . '/models/GPTUserFeedback.php'
]);

use Courseware\CoursewarePlugin;

class CoursewareGPTBlockPlugin extends StudIPPlugin implements SystemPlugin, CoursewarePlugin
{
    public function __construct()
    {
        parent::__construct();

        if (match_route('dispatch.php/*/courseware')) {
            PageLayout::addHeadElement('script', [], 'var COURSEWARE_GPT_BLOCK_BASE_URL = "' . dirname($this->url_for('api')) . '";');
            \PageLayout::addScript($this->getPluginUrl() . '/js/courseware-gpt-block.js');
            \PageLayout::addStylesheet($this->getPluginURL() . '/css/courseware-gpt-block.css');
            // Set block adder icon
            \PageLayout::addStyle('.cw-blockadder-item.cw-blockadder-item-gpt { background-image:url('.\Icon::create('chat')->asImagePath().')}');
        }

        // set up translation domain
        bindtextdomain('CoursewareGPTBlock', __DIR__ . '/locale');
    }

    /**
     * Implement this method to register more block types.
     *
     * You get the current list of block types and must return an updated list
     * containing your own block types.
     *
     * @param array $otherBlockTypes the current list of block types
     *
     * @return array the updated list of block types
     */
    public function registerBlockTypes(array $otherBlockTypes): array
    {
        $otherBlockTypes[] = CoursewareGPTBlock\GPTBlock::class;

        return $otherBlockTypes;
    }

    /**
     * Implement this method to register more container types.
     *
     * You get the current list of container types and must return an updated list
     * containing your own container types.
     *
     * @param array $otherContainerTypes the current list of container types
     *
     * @return array the updated list of container types
     */
    public function registerContainerTypes(array $otherContainerTypes): array
    {
        return $otherContainerTypes;
    }

    /**
     * Return a URL to a specified route in this plugin.
     * $params can contain optional additional parameters
     */
    public function url_for($path, $params = []): string
    {
        return PluginEngine::getURL($this, $params, $path);
    }
}
