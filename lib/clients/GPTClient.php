<?php

namespace CoursewareGPTBlock;

abstract class GPTClient
{
    private static $instance;

    /**
     * Get singleton instance of client
     *
     * @return static
     */
    public static function getInstance(): GPTClient
    {
        if (empty(self::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct() {}

    /**
     * Get api key
     *
     * @param string $api_key_origin 'global' | 'custom'
     * @param string $range_id id of course or user context
     *
     * @return string|null api key
     */
    protected function getApiKey(string $api_key_origin, string $range_id)
    {
        if ($api_key_origin === 'global') {
            return self::getGlobalApiKey();
        } else {
            return self::getCustomApiKey($range_id);
        }
    }

    /**
     * Loads global api key
     *
     * @return string OpenAI API Key
     */
    private static function getGlobalApiKey(): ?string
    {
        return \Config::get()->getValue('COURSEWARE_GPT_API_KEY');
    }

    /**
     * Checks if global api key is set
     *
     * @return bool global api key provided
     */
    public static function hasGlobalApiKey(): bool
    {
        return !empty(static::getGlobalApiKey());
    }

    /**
     * Loads custom api key from range config
     *
     * @param string $range_id id of course or user context
     *
     * @return string|null OpenAI API Key
     */
    private static function getCustomApiKey(string $range_id): ?string
    {
        return \RangeConfig::get($range_id)->getValue('COURSEWARE_GPT_CUSTOM_API_KEY');
    }

    /**
     * Stores custom api key in range config
     *
     * @param string $range_id id of course or user context
     * @param string $api_key OpenAI api key
     */
    public static function storeCustomApiKey(string $range_id, string $api_key)
    {
        \RangeConfig::get($range_id)->store('COURSEWARE_GPT_CUSTOM_API_KEY', $api_key);
    }

    /**
     * Checks if custom api key is set for given context
     *
     * @param string $range_id id of course or user context
     *
     * @return bool custom api key provided
     */
    public static function hasCustomApiKey(string $range_id): bool
    {
        return !empty(static::getCustomApiKey($range_id));
    }

    /**
     * Loads global api endpoint
     *
     * @return string API Endpoint
     */
    public static function getGlobalApiEndpoint(): ?string
    {
        return \Config::get()->getValue('COURSEWARE_GPT_ENDPOINT');
    }

    /**
     * Loads name of global chat model
     *
     * @return string global chat model
     */
    public static function getGlobalChatModel(): ?string
    {
        return \Config::get()->getValue('COURSEWARE_GPT_CHAT_MODEL');
    }

    /**
     * Sends a request to the LLM
     *
     * @param string $prompt LLM prompt
     * @param string $api_key_origin 'global' | 'custom'
     * @param string $range_id id of course or user context
     * @param string $endpoint api endpoint
     * @param string $chat_model chat model
     *
     * @return mixed json decoded response
     */
    public abstract function request(string $prompt, string $api_key_origin, string $range_id, string $endpoint, string $chat_model);
}