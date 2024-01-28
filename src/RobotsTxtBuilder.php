<?php

namespace MediaWiki\Extension\RobotsTxt;

class RobotsTxtBuilder
{
    /** @var string[] $lines */
    private $lines = [];

    /**
     * Add a Sitemap to the robots.txt.
     *
     * @param string $sitemap
     */
    public function addSitemap($sitemap)
    {
        $this->addLine("Sitemap: $sitemap");
    }

    /**
     * Add a User-agent to the robots.txt.
     *
     * @param string $userAgent
     */
    public function addUserAgent($userAgent)
    {
        $this->addLine("User-agent: $userAgent");
    }

    /**
     * Add a Host to the robots.txt.
     *
     * @param string $host
     */
    public function addHost($host)
    {
        $this->addLine("Host: $host");
    }

    /**
     * Add a disallow rule to the robots.txt.
     *
     * @param string|array $directories
     */
    public function addDisallow($directories)
    {
        $this->addRuleLine($directories, 'Disallow');
    }

    /**
     * Add a allow rule to the robots.txt.
     *
     * @param string|array $directories
     */
    public function addAllow($directories)
    {
        $this->addRuleLine($directories, 'Allow');
    }

    /**
     * Add a rule to the robots.txt.
     *
     * @param string|array $directories
     * @param string       $rule
     */
    protected function addRuleLine($directories, $rule)
    {
        foreach ((array) $directories as $directory) {
            $this->addLine("$rule: $directory");
        }
    }

    /**
     * Add a comment to the robots.txt.
     *
     * @param string $comment
     */
    public function addComment($comment)
    {
        $this->addLine("# $comment");
    }

    /**
     * Add empty lines
     * @param int $num number of lines
     */
    public function addSpacer(int $num = 1)
    {
        for ($i = 0; $i < $num; $i++) {
            $this->addLine(null);
        }
    }

    /**
     * Add a line
     *
     * @param string|null $line line to add
     */
    public function addLine(string | null $line)
    {
        $this->lines[] = (string) $line;
    }

    /**
     * Add multiple lines
     *
     * @param string|array $lines lines to add
     */
    public function addLines($lines)
    {
        foreach ((array) $lines as $line) {
            $this->addLine($line);
        }
    }

    public function getOutput()
    {
        return implode(PHP_EOL, $this->lines);
    }
}
