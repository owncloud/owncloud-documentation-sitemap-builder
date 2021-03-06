<?php

namespace OwnCloud\SiteMapBuilder;

use OwnCloud\SiteMapBuilder\Iterator\RestructuredTextFilterIterator;
use Refinery29\Sitemap\Component;
use Refinery29\Sitemap\Writer;

/**
 * Class SitemapBuilder
 * @package OwnCloud\SiteMapBuilder
 */
class SitemapBuilder
{
    const BASE_PATH = 'https://doc.owncloud.com/server';
    const PRIORITY = 0.8;
    const VERSION = '10.0';
    const FILEPATH_PREFIX_REGEX = '/.*(?=user_manual|admin_manual|developer_manual)/';

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $version;

    /**
     * @var float
     */
    private $priority;

    /**
     * @var string
     */
    private $changeFrequency;

    /**
     * SitemapBuilder constructor.
     * @param string $basePath
     * @param string $version
     * @param float|string $priority
     * @param string $changeFrequency
     */
    public function __construct(
        $basePath = self::BASE_PATH,
        $version = self::VERSION,
        $priority = self::PRIORITY,
        $changeFrequency = Component\Url::CHANGE_FREQUENCY_WEEKLY
    ) {
        $this->basePath = $basePath;
        $this->changeFrequency = $changeFrequency;
        $this->version = $version;
        $this->priority = $priority;
    }

    /**
     * Build a list of URLs from the files of the required filetype retrieved
     * @param string $path
     * @param string $extension
     * @return array
     */
    public function getSitemapUrlList($path, $extension)
    {
        // retrieve a filtered list of file names, based on the specified extension.
        $iterator = new RestructuredTextFilterIterator(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::CURRENT_AS_PATHNAME)
        ), $extension);

        return $this->convertFilesToUrls($iterator, $extension);
    }

    /**
     * Generated a valid sitemap.xml string from the list of files supplied
     * @param array $fileList
     * @return string
     */
    public function generateSiteMapXml($fileList)
    {
        $urlList = [];

        foreach ($fileList as $file) {
            $url = new Component\Url($file);
            $urlList[] = $url
                ->withLastModified(new \DateTime())
                ->withChangeFrequency($this->changeFrequency)
                ->withPriority($this->priority)
            ;
        }

        $urlSetWriter = new Writer\UrlSetWriter();

        return $urlSetWriter->write(new Component\UrlSet($urlList));
    }

    /**
     * Convert a list of retrieved files to an equivalent list of URLs
     *
     * @param \Traversable $iterator
     * @param string $extension
     * @return array
     */
    private function convertFilesToUrls(\Traversable $iterator, $extension)
    {
        $urlList = [];

        foreach ($iterator as $filename) {
            $url = str_replace(
                ['\\', $extension],
                ['/', 'html'],
                preg_replace(self::FILEPATH_PREFIX_REGEX, '', $filename)
            );
            $urlList[] = sprintf('%s/%s/%s', $this->basePath, $this->version, $url);
        }

        return $urlList;
    }
}
