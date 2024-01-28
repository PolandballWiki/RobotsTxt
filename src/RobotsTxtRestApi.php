<?php

namespace MediaWiki\Extension\RobotsTxt;

use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use RequestContext;

class RobotsTxtRestApi extends SimpleHandler {
    private RequestContext $requestContext;

	/** @inheritDoc */
	public function run() {
        $requestContext = new RequestContext();
        $this->requestContext = $requestContext;

        $responseFactory = $this->getResponseFactory();
        $response = $responseFactory->create();

        $robotsTxt = $this->buildRobotsTxt();
        $body = new StringStream($robotsTxt);

        $response->setHeader('Cache-Control', 'public, max-age=86400');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setBody($body);
        return $response;
	}

    public function buildRobotsTxt () {
        $siteName = $this->requestContext->getConfig()->get('Sitename');
        $robotsBuilder = new RobotsTxtBuilder();

        $robotsBuilder->addComment('robots.txt file for '. $siteName);
        $robotsBuilder->addSpacer();
        
        $robotsBuilder = $this->buildBlockedNamespaces($robotsBuilder);
        $robotsBuilder = $this->buildBlockedParams($robotsBuilder);
        $robotsBuilder = $this->buildAllowedSpecialPages($robotsBuilder);
        $robotsBuilder = $this->buildAllowedPaths($robotsBuilder);
        $robotsBuilder = $this->buildThrottlingRules($robotsBuilder);
        $robotsBuilder = $this->buildBlockingRules($robotsBuilder);
        
        return $robotsBuilder->getOutput();
    }

    public function buildBlockedNamespaces(RobotsTxtBuilder $builder) {
        $pathBuilder = new RobotsTxtPathBuilder();
        $blockedNamespaces = $pathBuilder->getBlockedNamespaces();

        $builder->addUserAgent('*');
        $builder->addDisallow($blockedNamespaces);
        $builder->addSpacer();
        return $builder;
    }

    public function buildBlockedParams(RobotsTxtBuilder $builder) {
        $pathBuilder = new RobotsTxtPathBuilder();
        $blockedParams = $pathBuilder->getBlockedParams();

        $builder->addComment('Disallow certain parameters, e.g. old revisions and editing pages');
        $builder->addUserAgent('*');
        $builder->addDisallow($blockedParams);
        $builder->addSpacer();

        return $builder;
    }

    public function buildAllowedSpecialPages(RobotsTxtBuilder $builder) {
        $pathBuilder = new RobotsTxtPathBuilder();
        $allowedSpecialPages = $pathBuilder->getAllowedSpecialPages();

        $builder->addComment('Allow certain special pages');
        $builder->addUserAgent('*');
        $builder->addAllow($allowedSpecialPages);
        $builder->addSpacer();

        return $builder;
    }

    public function buildAllowedPaths(RobotsTxtBuilder $builder) {
        $pathBuilder = new RobotsTxtPathBuilder();
        $allowedPaths = $pathBuilder->getAllowedPaths();

        $builder->addComment('Allow certain paths');
        $builder->addUserAgent('*');
        $builder->addAllow($allowedPaths);
        $builder->addSpacer();

        return $builder;
    }

    public function buildThrottlingRules(RobotsTxtBuilder $builder) {
        $throttledBots = [
            'YandexBot' => 5,
            'Bingbot' => 5,
            'MJ12Bot' => 10
        ];

        foreach ($throttledBots as $botName => $delay) {
            $builder->addComment('Throttle '. $botName);
            $builder->addUserAgent($botName);
            $builder->addLine('Crawl-Delay: ' . $delay);
            $builder->addSpacer();
        }

        return $builder;
    }

    public function buildBlockingRules(RobotsTxtBuilder $builder) {
        $blockedBots = [
            'Barkrowler',
            'Bytespider',
            'MegaIndex', 
            'PetalBot',
            'SemrushBot',
            'serpstatbot'
        ];

        foreach ($blockedBots as $bot) {
            $builder->addComment('Block ' . $bot);
            $builder->addUserAgent($bot);
            $builder->addDisallow('/');
            $builder->addSpacer();
        }

        return $builder;
    }

	/** @inheritDoc */
	public function needsWriteAccess() {
		return false;
	}
}