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

    // List of blocked URL parameters for crawlers
    private $blockedParams = [
		'action',
		'feed',
		'from', // user-supplied legacy MW pagination
		'oldid',
		'printable',
		'redirect',
		'useskin',
		'uselang',
		'veaction',
	];

    // List of allowed special pages (normally all special pages are blocked)
    private $allowedSpecialPages = [
        'Newpages'
    ];

    // Allowed paths, in addition to allowed special pages
    private $allowedPaths = [
        // Allow bots to crawl api.php responses
		'/api.php?',
		'/api.php?action=',
		'/api.php?*&action='
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

    public function getBlockedNamespaces () {
        $paths = [];

        foreach ($this->blockedNamespaces as $namespace) {
            $aliases = $this->getNamespaceAliases($namespace);
            $paths = array_merge($paths, $this->buildPagePaths($aliases, '', ':'));
        }

        return $paths;
    }

    public function getBlockedParams() {
        $paths = [];

        foreach ($this->blockedParams as $param) {
            $paths = array_merge($paths, $this->buildPathsForParam($param));
        }

        return $paths;
    }

    public function getAllowedSpecialPages() {
        $paths = [];

        foreach ($this->allowedSpecialPages as $page) {
            $paths = array_merge($paths, $this->buildPathsForSpecialPage($page));
        }

        return $paths;
    }

    public function getAllowedPaths() {
        $paths = [];
        $config = $this->requestContext->getConfig();
        $scriptPath = $config->get('ScriptPath');

        foreach ($this->allowedPaths as $allowedPath) {
            $paths[] = $scriptPath . $allowedPath;
        }

        return $paths;
    } 

    /**
     * Build a list of paths from where the page is accessible at:
	 *  * /wiki/PAGENAME (the canonical way)
	 *  * /index.php?title=PAGENAME
	 *  * /index.php/PAGENAME
     * @param string[] $pages
     * @param string prefix
     * @param string suffix
     * @param bool canonicalOnly
     * 
    */
    private function buildPagePaths(array $pages, string $prefix = '', string $suffix = '', bool $canonicalOnly = false) {
        /** @var string[] $pagePaths */
        $pagePaths = [];

        $config = $this->requestContext->getConfig();
        $scriptPath = $config->get('ScriptPath');
        $articlePath = $config->get('ArticlePath');

        foreach ($pages as $pageName) {
            $encodedPageName = $this->encodeUrl($pageName);
            $pagePaths[] = StringUtils::replaceMarkup('$1', $prefix . $encodedPageName . $suffix, $articlePath);
            if (!$canonicalOnly) {
                $pagePaths[] = StringUtils::replaceMarkup('$1', $prefix . $encodedPageName . $suffix, $scriptPath . '/*?*title=$1');
                $pagePaths[] = StringUtils::replaceMarkup('$1', $prefix . $encodedPageName . $suffix, $scriptPath . '/index.php/$1');
            }
        }

        return $pagePaths;
    }

    private function buildPathsForSpecialPage(string $specialPageName) {
        $paths = [];

        foreach ($this->languages as $language) {
            $specialPageAliases = $language->getSpecialPageAliases();
            $pageAliases = $specialPageAliases[$specialPageName];

            if (isset($pageAliases) && is_array($pageAliases)) {
                $canonicalNames = [];

                foreach (array_unique($pageAliases) as $alias) {
                    $canonicalNames[] = $this->getCanonicalPageName($alias, NS_SPECIAL, $language);
                }

                $paths = array_merge($paths, $this->buildPagePaths(array_unique($canonicalNames)));
            }
        }

        return $paths;
    }

    private function getCanonicalPageName(string $pageName, int $ns, Language $lang) {
        if ($ns === NS_MAIN) {
            return $pageName;
        }

        $nsName = $lang->getNsText($ns);
        return $nsName . ':' . $pageName;
    }

    /**
	 * Build path matching URLs with a specific param present
	 *
	 * This will only work for robots that understand wildcards.
	 *
	 * @param string $param URL param to block
	 * @return array
	 */
	private function buildPathsForParam($param) {
        $config = $this->requestContext->getConfig();
        $scriptPath = $config->get('ScriptPath');

		return [
			$scriptPath . '/*?' . $param . '=' ,
			$scriptPath . '/*?*&' . $param . '=' ,
		];
	}

    private function getNamespaceAliases (int $ns) {
        $namespaceAliases = [];

        foreach($this->languages as $language) {
            $namespaceAliases[] = $language->getNsText($ns);
        }

        return $namespaceAliases;
    }

    /**
	 * Encode URL in a way you can safely put that to robots.txt
	 *
	 * There's no need to encode characters like /, :, *, ?, &, =, $, so they are decoded
	 * Non-English characters WILL be encoded.
	 *
	 * @param string $in the URL to encode
	 * @return string
	 */
	private function encodeUrl($in) {
		return str_replace(
			[ '%2F', '%3A', '%2A', '%3F', '%26', '%3D', '%24' ],
			[ '/', ':', '*', '?', '&', '=', '$' ],
			rawurlencode($in)
		);
	}
}
