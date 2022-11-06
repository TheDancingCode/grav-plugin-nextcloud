<?php

namespace Grav\Plugin;

use DateTime;
use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Class NextcloudPlugin
 * @package Grav\Plugin
 */
class NextcloudPlugin extends Plugin
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBackupFinished' => ['onBackupFinished', 0],
        ];
    }

    /**
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    public function onBackupFinished($event)
    {
        $this->uploadBackup($event['backup']);
    }

    public function uploadBackup($backup)
    {
        $config = $this->config->get('plugins.nextcloud');

        $webdav_url = rtrim($config['webdav_url'], '/') . '/';
        $webdav_url_regexp = '/((?:.*)\/remote\.php\/dav\/)(?:files|uploads)(\/(?:.*)\/)/';
        $files_url = preg_replace($webdav_url_regexp, '$1files$2', $webdav_url);
        $uploads_url = preg_replace($webdav_url_regexp, '$1uploads$2', $webdav_url);

        $client = HttpClient::create([
            'auth_basic' => [$config['username'], $config['password']]
        ]);

        // Step 1: Create temporary upload folder
        $chunks_folder = basename($backup, '.zip');
        $chunks_url = $uploads_url . $chunks_folder;

        $client->request('MKCOL', $chunks_url);

        // Step 2: Upload chunks
        $filesize = filesize($backup);
        $filesize_length = strlen((string)$filesize);
        $chunkSize = 5 * 1024 * 1024;

        $fh = fopen($backup, 'rb');

        while (!feof($fh)) {
            $start = str_pad((string)ftell($fh), $filesize_length, '0', STR_PAD_LEFT);
            $chunk = fread($fh, $chunkSize);
            $end = str_pad((string)ftell($fh), $filesize_length, '0', STR_PAD_LEFT);

            $client->request('PUT', $chunks_url . '/' . $start . '-' . $end, [
                'body' => $chunk
            ]);
        }

        fclose($fh);

        // Step 3: Create destination folder if it does not exist
        $folder = trim($config['folder'], '/') . '/';
        $destination_url = $files_url . $folder;

        $response = $client->request('PROPFIND', $destination_url);
        if ($response->getStatusCode() === 404) {
            $client->request('MKCOL', $destination_url);
        }

        // Step 4: Move chunks to final destination
        $filename = basename($backup);

        $client->request('MOVE', $chunks_url . '/.file', [
            'headers' => [
                'Destination' => $destination_url . $filename
            ]
        ]);

        $this->purge();
    }

    public function listBackups()
    {
        $config = $this->config->get('plugins.nextcloud');

        $webdav_url = rtrim($config['webdav_url'], '/') . '/';
        $webdav_url_regexp = '/((?:.*)\/remote\.php\/dav\/)(?:files|uploads)(\/(?:.*)\/)/';
        $files_url = preg_replace($webdav_url_regexp, '$1files$2', $webdav_url);
        $folder = trim($config['folder'], '/') . '/';
        $destination_url = $files_url . $folder;

        $client = HttpClient::create([
            'auth_basic' => [$config['username'], $config['password']]
        ]);

        $response = $client->request('PROPFIND', $destination_url);

        $xml = simplexml_load_string($response->getContent(), null, 0, 'd', true);

        $backups = [];

        foreach ($xml->response as $entry) {
            $filename = basename((string)$entry->href);
            if (preg_match('/(.*)--(\d*)\.zip/', $filename)) {
                $size = (int)$entry->propstat->prop->getcontentlength;
                $datetime = DateTime::createFromFormat('YmdHis', substr(basename($filename, '.zip'), -14));
                $backups[] = [
                    'filename' => $filename,
                    'size' => $size,
                    'datetime' => $datetime
                ];
            }
        }

        usort($backups, function ($a, $b) {
            return $b['datetime'] <=> $a['datetime'];
        });

        return $backups;
    }

    public function purge()
    {
        $config = $this->config->get('plugins.nextcloud');

        $webdav_url = rtrim($config['webdav_url'], '/') . '/';
        $webdav_url_regexp = '/((?:.*)\/remote\.php\/dav\/)(?:files|uploads)(\/(?:.*)\/)/';
        $files_url = preg_replace($webdav_url_regexp, '$1files$2', $webdav_url);
        $folder = trim($config['folder'], '/') . '/';
        $destination_url = $files_url . $folder;

        $client = HttpClient::create([
            'auth_basic' => [$config['username'], $config['password']]
        ]);

        $trigger = $config['purge']['trigger'];
        $backups = $this->listBackups();

        switch ($trigger) {
            case 'none':
                break;

            case 'number':
                $this->grav['debugger']->addMessage('case: number');
                $backups_count = count($backups);
                if ($backups_count > $config['purge']['max_backups_count']) {
                    $last = end($backups);
                    $filename = $last['filename'];
                    $client->request('DELETE', $destination_url . $filename);
                }
                break;

            case 'time':
                $this->grav['debugger']->addMessage('case: time');
                $last = end($backups);
                $now = new DateTime();
                $interval = $now->diff($last['datetime']);
                if ($interval->days > $config['purge']['max_backups_time']) {
                    $filename = $last['filename'];
                    $client->request('DELETE', $destination_url . $filename);
                }
                break;

            default:
                $this->grav['debugger']->addMessage('case: space');
                $used_space = array_sum(array_column($backups, 'size'));
                $max_space = $config['purge']['max_backups_space'] * 1024 * 1024 * 1024;
                if ($used_space > $max_space) {
                    $last = end($backups);
                    $filename = $last['filename'];
                    $client->request('DELETE', $destination_url . $filename);
                }
                break;
        }
    }
}
