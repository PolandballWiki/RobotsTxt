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
        $robotsBuilder = $this->buildThrottlingRules($robotsBuilder);
        $robotsBuilder = $this->buildBlockingRules($robotsBuilder);
        
        return $robotsBuilder->getOutput();
    }

    public function buildBlockedNamespaces (RobotsTxtBuilder $builder) {
        $pathBuilder = new RobotsTxtPathBuilder();

        $blockedNamespaces = $pathBuilder->getBlockedNamespaces();
        $blockedNamespacePaths = $pathBuilder->buildContentPaths($blockedNamespaces, '', ':');

        $builder->addUserAgent('*');
        $builder->addDisallow($blockedNamespacePaths);
        $builder->addSpacer();
        return $builder;
    }

    public function buildThrottlingRules(RobotsTxtBuilder $builder) {
        $throttledBots = [
            'YandexBot' => 2.5,
            'Bingbot' => 5
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
        $blockedBots = ['Bytespider', 'PetalBot', 'MegaIndex'];

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