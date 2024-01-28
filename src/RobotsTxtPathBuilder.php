<?php 

namespace MediaWiki\Extension\RobotsTxt;

use Language;
use RequestContext;
use MediaWiki\MediaWikiServices;
use StringUtils;

class RobotsTxtPathBuilder {
    private $blockedNamespaces = [
        NS_SPECIAL,
        NS_TEMPLATE,
        NS_TEMPLATE_TALK
    ];

    private RequestContext $requestContext;

    /** @var Language[] $languages */
    private array $languages = [];

    public function __construct()
    {
        $requestContext = new RequestContext();
        $this->requestContext = $requestContext;

        $instance =  MediaWikiServices::getInstance();
        $contentLanguage = $instance->getContentLanguage();

        $this->languages = [ $contentLanguage ];

        if ($contentLanguage-> getCode() !== 'en') {
            $this->languages[] = $instance->getLanguageFactory()->getLanguage('en');
        }
    }

    /**
     * Build a list of paths with the article path prefix (e.g., /wiki/$1)
     * @param string[] $paths
     * 
    */
    public function buildContentPaths (array $paths, string $prefix = '', string $suffix = '') {
        /** @var string[] $contentPaths */
        $contentPaths = [];

        $config = $this->requestContext->getConfig();
        $articlePath = $config->get('ArticlePath');

        foreach ($paths as $path) {
            $contentPaths[] = StringUtils::replaceMarkup('$1', $prefix . $path . $suffix, $articlePath);
        }

        return $contentPaths;
    }

    public function getBlockedNamespaces () {
        $namespaces = [];

        foreach ($this->blockedNamespaces as $namespace) {
            foreach ($this->languages as $language) {
                $namespaces[] = $language->getNsText($namespace);
            }
        }

        return $namespaces;
    }

    public function getLanguages () {
        return $this->languages;
    }
}
