<?php

class Mixcloud extends Downloader
{
    public function fetch($videoUrl)
    {
        $explode = explode('/', $videoUrl);
        if (!empty($explode[3]) && !empty($explode[4])) {
            $username = $explode[3];
            $slug = $explode[4];
            $query = '{"query":"query StyleOverrideQuery(\n  $lookup: CloudcastLookup!\n) {\n  cloudcast: cloudcastLookup(lookup: $lookup) {\n    picture {\n      primaryColor\n      isLight\n      lightPrimaryColor: primaryColor(lighten: 15)\n      darkPrimaryColor: primaryColor(darken: 15)\n    }\n    id\n  }\n}\n","variables":{"lookup":{"username":"' . $username . '","slug":"' . $slug . '"}}}';
            $response = json_decode($this->graphqlRequest($query), true);
            if (!empty($response['data']['cloudcast']['id'])) {
                $cloudcastId = $response['data']['cloudcast']['id'];
                $query = '{"query":"query PlayerControlsQuery(\n  $cloudcastId: ID!\n) {\n  cloudcast(id: $cloudcastId) {\n    id\n    name\n    slug\n    isPublic\n    isExclusive\n    isExclusivePreviewOnly\n    repeatPlayAmount\n    owner {\n      id\n      username\n      isFollowing\n      isViewer\n      displayName\n      followers {\n        totalCount\n      }\n    }\n    picture {\n      ...UGCImage_picture\n    }\n    ...PlayerActions_cloudcast\n    ...PlayerSeekingActions_cloudcast\n    ...PlayerSliderComponent_cloudcast\n  }\n  viewer {\n    ...PlayerActions_viewer\n    ...PlayerSeekingActions_viewer\n    ...PlayerSliderComponent_viewer\n    me {\n      id\n    }\n    id\n  }\n}\n\nfragment PlayerActionsFavoriteButton_cloudcast on Cloudcast {\n  id\n  isPublic\n  isFavorited\n  owner {\n    id\n    username\n    displayName\n    isSelect\n    isFollowing\n    isViewer\n  }\n  favorites {\n    totalCount\n  }\n  slug\n}\n\nfragment PlayerActionsFavoriteButton_viewer on Viewer {\n  me {\n    id\n  }\n}\n\nfragment PlayerActions_cloudcast on Cloudcast {\n  isPublic\n  owner {\n    isViewer\n    id\n  }\n  ...PlayerActionsFavoriteButton_cloudcast\n  ...PlayerMoreMenuPopover_cloudcast\n}\n\nfragment PlayerActions_viewer on Viewer {\n  ...PlayerActionsFavoriteButton_viewer\n  ...PlayerMoreMenuPopover_viewer\n}\n\nfragment PlayerMenuRepostAction_cloudcast on Cloudcast {\n  id\n  isReposted\n  isPublic\n  reposts {\n    totalCount\n  }\n  owner {\n    isViewer\n    id\n  }\n}\n\nfragment PlayerMenuRepostAction_viewer on Viewer {\n  me {\n    id\n  }\n}\n\nfragment PlayerMenuShareAction_cloudcast on Cloudcast {\n  ...ShareCloudcastButton_cloudcast\n}\n\nfragment PlayerMenuViewProfileAction_cloudcast on Cloudcast {\n  owner {\n    username\n    id\n  }\n}\n\nfragment PlayerMenuViewTracklistAction_cloudcast on Cloudcast {\n  canShowTracklist\n  sections {\n    __typename\n    ... on TrackSection {\n      id\n    }\n    ... on ChapterSection {\n      id\n    }\n    ... on Node {\n      __isNode: __typename\n      id\n    }\n  }\n  slug\n  owner {\n    username\n    id\n  }\n}\n\nfragment PlayerMoreMenuPopover_cloudcast on Cloudcast {\n  ...PlayerMenuRepostAction_cloudcast\n  ...PlayerMenuViewTracklistAction_cloudcast\n  ...PlayerMenuViewProfileAction_cloudcast\n  ...PlayerMenuShareAction_cloudcast\n}\n\nfragment PlayerMoreMenuPopover_viewer on Viewer {\n  ...PlayerMenuRepostAction_viewer\n}\n\nfragment PlayerSeekingActions_cloudcast on Cloudcast {\n  id\n  repeatPlayAmount\n  seekRestriction\n  ...SeekButton_cloudcast\n}\n\nfragment PlayerSeekingActions_viewer on Viewer {\n  hasRepeatPlayFeature: featureIsActive(switch: \"repeat_play\")\n}\n\nfragment PlayerSliderComponent_cloudcast on Cloudcast {\n  id\n  waveformUrl\n  owner {\n    id\n    isFollowing\n    isViewer\n  }\n  isExclusive\n  seekRestriction\n  ...SeekWarning_cloudcast\n  sections {\n    __typename\n    ... on TrackSection {\n      artistName\n      songName\n      startSeconds\n    }\n    ... on ChapterSection {\n      chapter\n      startSeconds\n    }\n    ... on Node {\n      __isNode: __typename\n      id\n    }\n  }\n  repeatPlayAmount\n}\n\nfragment PlayerSliderComponent_viewer on Viewer {\n  restrictedPlayer: featureIsActive(switch: \"restricted_player\")\n}\n\nfragment SeekButton_cloudcast on Cloudcast {\n  id\n  repeatPlayAmount\n  seekRestriction\n  owner {\n    isSelect\n    id\n  }\n}\n\nfragment SeekWarning_cloudcast on Cloudcast {\n  owner {\n    displayName\n    isSelect\n    username\n    id\n  }\n  seekRestriction\n}\n\nfragment ShareCloudcastButton_cloudcast on Cloudcast {\n  id\n  isUnlisted\n  isPublic\n  slug\n  description\n  picture {\n    urlRoot\n  }\n  owner {\n    displayName\n    isViewer\n    username\n    id\n  }\n}\n\nfragment UGCImage_picture on Picture {\n  urlRoot\n  primaryColor\n}\n","variables":{"cloudcastId":"' . $cloudcastId . '"}}';
                $data = json_decode($this->graphqlRequest($query), true);
                if (!empty($data['data']['cloudcast']['waveformUrl'])) {
                    preg_match('/waveform.mixcloud.com(.*?).json/', $data['data']['cloudcast']['waveformUrl'], $matches);
                    $this->title = $data['data']['cloudcast']['name'];
                    $this->source = 'mixcloud';
                    $this->thumbnail = 'https://thumbnailer.mixcloud.com/unsafe/300x300/' . $data['data']['cloudcast']['picture']['urlRoot'];
                    if (!empty($matches[1])) {
                        $mpdUrl = 'https://audio11.mixcloud.com/secure/dash2' . $matches[1] . '.m4a/manifest.mpd';
                        $http = new Http($mpdUrl);
                        $http->run();
                        preg_match_all('/media="(.*?)"[\s\S]*? r="(\d+)"[\s\S]*? id="(.*?)"/', $http->response, $matches);
                        if (count($matches) >= 4) {
                            $baseUrl = $matches[1][0];
                            $fragmentCount = $matches[2][0];
                            $id = $matches[3][0];
                            $baseUrl = str_replace('$RepresentationID$', $id, $baseUrl);
                            $chunks = [];
                            for ($i = 1; $i <= $fragmentCount; $i++) {
                                $chunks[] = str_replace('$Number$', $i, $baseUrl);
                            }
                            $cache = Helpers::createChunkCache($chunks, 'mixcloud-' . $cloudcastId);
                            $chunkSize = Helpers::getChunkedSize($chunks[0], count($chunks));
                            $media = new Media($cache->url, '256kbps', 'm4a', false, true);
                            $media->size = $chunkSize;
                            $media->chunked = true;
                            $this->medias[] = $media;
                        }
                    }
                }
            }
        }
    }

    private function graphqlRequest($query)
    {
        $http = new Http('https://www.mixcloud.com/graphql');
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->addCurlOption(CURLOPT_POSTFIELDS, $query);
        $http->addHeader('Content-Type', 'application/json');
        $http->run();
        return $http->response;
    }
}