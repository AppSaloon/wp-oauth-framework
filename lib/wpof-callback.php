<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace wp_oauth_framework;

use fkooman\OAuth\Client\Callback;
use fkooman\OAuth\Client\Exception\CallbackException;
// FIXME: replace AuthorizeException with CallbackException?
use fkooman\OAuth\Client\Exception\AuthorizeException;
use fkooman\Oauth\Client\ClientConfigInterface;
use fkooman\Oauth\Client\StorageInterface;
use fkooman\Oauth\Client\HttpClientInterface;
use fkooman\OAuth\Client\RefreshToken;

class WPOF_Callback extends Callback
{
//    public function __construct(
//        $clientConfigId,
//        ClientConfigInterface $clientConfig,
//        StorageInterface $tokenStorage,
//        HttpClientInterface $httpClient
//    ) {
//        parent::__construct( $clientConfigId, $clientConfig, $tokenStorage, $httpClient );
//    }

    public function handleCallback(array $query)
    {
        $queryState = isset($query['state']) ? $query['state'] : null;
        $queryCode = isset($query['code']) ? $query['code'] : null;
        $queryError = isset($query['error']) ? $query['error'] : null;
        $queryErrorDescription = isset($query['error_description']) ? $query['error_description'] : null;

        if (null === $queryState) {
            throw new CallbackException('state parameter missing');
        }
        $state = $this->getTokenStorage()->getState($this->getClientConfigId(), $queryState);
        if (false === $state) {
            throw new CallbackException('state not found');
        }

        // avoid race condition for state by really needing a confirmation
        // that it was deleted
        if (false === $this->getTokenStorage()->deleteState($state)) {
            throw new CallbackException('state already used');
        }

        if (null === $queryCode && null === $queryError) {
            throw new CallbackException('both code and error parameter missing');
        }

        if (null !== $queryError) {
            // FIXME: this should probably be CallbackException?
            throw new AuthorizeException($queryError, $queryErrorDescription);
        }

        if (null !== $queryCode) {
            $t = new WPOF_Token_Request($this->getHttpClient(), $this->getClientConfig());
            $tokenResponse = $t->withAuthorizationCode($queryCode);
            if (false === $tokenResponse) {
                throw new CallbackException('unable to fetch access token with authorization code');
            }

            if (null === $tokenResponse->getScope()) {
                // no scope in response, we assume we got the initially requested scope
                $scope = $state->getScope();
            } else {
                // the scope we got should be a superset of what we requested
                $scope = $tokenResponse->getScope();
                if (!$scope->hasScope($state->getScope())) {
                    // we didn't get the scope we requested, stop for now
                    // FIXME: we need to implement a way to request certain
                    // scope as being optional, while others need to be
                    // required
                    throw new CallbackException('requested scope not obtained');
                }
            }

            // store the access token
            $accessToken = new WPOF_Access_Token(
                array(
                    'client_config_id' => $this->getClientConfigId(),
                    'user_id' => $state->getUserId(),
                    'scope' => $scope,
                    'access_token' => $tokenResponse->getAccessToken(),
                    'token_type' => $tokenResponse->getTokenType(),
                    'issue_time' => time(),
                    'expires_in' => $tokenResponse->getExpiresIn(),
                    'user_id_from_provider' => $tokenResponse->get_user_id_from_provider(),
                )
            );
            $this->getTokenStorage()->storeAccessToken($accessToken);

            // if we also got a refresh token in the response, store that as
            // well
            if (null !== $tokenResponse->getRefreshToken()) {
                $refreshToken = new RefreshToken(
                    array(
                        'client_config_id' => $this->getClientConfigId(),
                        'user_id' => $state->getUserId(),
                        'scope' => $scope,
                        'refresh_token' => $tokenResponse->getRefreshToken(),
                        'issue_time' => time(),
                    )
                );
                $this->getTokenStorage()->storeRefreshToken($refreshToken);
            }

            return $accessToken;
        }
    }
}
