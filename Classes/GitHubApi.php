<?php

namespace Classes;

class GitHubApi
{
    const API_HOST = 'https://api.github.com';

    private $owner;

    private $repo;

    private $branch;

    public function __construct($owner, $repo)
    {
        $this->owner = $owner;
        $this->repo = $repo;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPaths()
    {
        if (empty($this->owner) || empty($this->repo)) {
            throw new \Exception('No repo owner or name provided');
        }

        $treeSha = $this->getTreeSha();

        $response = $this->get(
            sprintf(
                '/repos/%s/%s/git/trees/%s?recursive=1',
                $this->owner,
                $this->repo,
                $treeSha
            )
        );

        if (!empty($response['tree'])) {
            $tree = array_filter(
                $response['tree'],
                function ($value) {
                    return $value['type'] === 'blob';
                }
            );

            return array_column($tree, 'path');
        } else {
            throw new \Exception('No tree found');
        }
    }

    /**
     * @param $path
     * @param $branch
     * @return bool|string
     * @throws \Exception
     */
    public function getContentByPath($path, $branch)
    {
        if (empty($this->owner) || empty($this->repo)) {
            throw new \Exception('No repo owner or name provided');
        }

        $url = sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/%s',
            $this->owner,
            $this->repo,
            $branch,
            $path
        );

        $headers = get_headers($url, 1);

        if (!strstr($headers['Content-Type'], 'text/plain')) {
            throw new \Exception('Content type not plain text');
        }

        $content = file_get_contents($url);

        if ($content) {
            return $content;
        } else {
            throw new \Exception('No content found');
        }
    }

    /**
     * @return mixed
     */
    public function getBranch()
    {
        return $this->branch;
    }

    private function get($endpoint)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => self::API_HOST . $endpoint,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
            CURLOPT_HTTPHEADER => ['Authorization: token ' . getenv('GITHUB_API_TOKEN')]
        ]);

        $result = curl_exec($curl);

        return json_decode($result, true);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getTreeSha()
    {
        $defaultBranch = $this->getDefaultBranch();

        $response = $this->get(
            sprintf(
                '/repos/%s/%s/branches/%s',
                $this->owner,
                $this->repo,
                $defaultBranch
            )
        );

        if (!empty($response['commit']['commit']['tree']['sha'])) {
            return $response['commit']['commit']['tree']['sha'];
        } else {
            throw new \Exception('No tree sha found');
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getDefaultBranch()
    {
        $response = $this->get(
            sprintf(
                '/repos/%s/%s',
                $this->owner,
                $this->repo
            )
        );

        if (!empty($response['default_branch'])) {
            $this->branch = $response['default_branch'];

            return $this->branch;
        } else {
            throw new \Exception('No repo found');
        }
    }
}