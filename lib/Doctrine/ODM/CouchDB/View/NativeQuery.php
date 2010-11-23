<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\CouchDB\View;

use Doctrine\ODM\CouchDB\HTTP\Client;
use Doctrine\ODM\CouchDB\HTTP\ErrorResponse;

/**
 * Query Object for Views
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class NativeQuery
{
    /**
     * @var DesignDocument
     */
    private $doc;

    /**
     * @var string
     */
    private $designDocumentName;

    /**
     * @var string
     */
    private $viewName;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $params;

    /**
     * @param Client $client
     * @param string $databaseName
     * @param string $viewName
     * @param DesignDocument $doc
     */
    public function __construct(Client $client, $databaseName, $designDocName, $viewName, DesignDocument $doc = null)
    {
        $this->client = $client;
        $this->databaseName = $databaseName;
        $this->designDocumentName = $designDocName;
        $this->viewName = $viewName;
        $this->doc = $doc;
    }

    /**
     * Query the view with the current params.
     *
     * @return array
     */
    public function execute()
    {
        $response = $this->client->request(
            "GET",
            $path = sprintf(
                "/%s/_design/%s/_view/%s?%s",
                $this->databaseName,
                $this->designDocumentName,
                $this->viewName,
                http_build_query( array_map( "json_encode", $this->params ) )
            )
        );

        if ( $response instanceof ErrorResponse ) {
            // Create view, if it does not exist yet
            $this->createView();
            $response = $this->client->request( "GET", $path );
        }


        if ($response->status >= 400) {
            throw new \Exception("Error [" . $response->status . "]: " . $response->body['error'] . " " . $response->body['reason']);
        }

        return $response->body['rows'];
    }

    /**
     * Find key in view.
     *
     * @param  string $val
     * @return Query
     */
    public function setKey($val)
    {
        $this->params['key'] = $val;
        return $this;
    }

    /**
     * Set starting key to query view for.
     *
     * @param  string $val
     * @return Query
     */
    public function setStartKey($val)
    {
        $this->params['startkey'] = $val;
        return $this;
    }

    /**
     * Set ending key to query view for.
     *
     * @param  string $val
     * @return Query
     */
    public function setEndKey($val)
    {
        $this->params['endkey'] = $val;
        return $this;
    }

    /**
     * Document id to start with
     *
     * @param  string $val
     * @return Query
     */
    public function setStartKeyDocId($val)
    {
        $this->params['startkey_docid'] = $val;
        return $this;
    }

    /**
     * Last document id to include in the output
     *
     * @param  string $val
     * @return Query
     */
    public function setEndKeyDocId($val)
    {
        $this->params['endkey_docid'] = $val;
        return $this;
    }

    /**
     * Limit the number of documents in the output
     *
     * @param  int $val
     * @return Query
     */
    public function setLimit($val)
    {
        $this->params['limit'] = $val;
        return $this;
    }

    /**
     * Skip n number of documents
     *
     * @param  int $val
     * @return Query
     */
    public function setSkip($val)
    {
        $this->params['skip'] = $val;
        return $this;
    }

    /**
     * If stale=ok is set CouchDB will not refresh the view even if it is stalled.
     *
     * @param  bool $flag
     * @return Query
     */
    public function setStale($flag)
    {
        $this->params['stale'] = $flag;
        return $this;
    }

    /**
     * reverse the output
     *
     * @param  bool $flag
     * @return Query
     */
    public function setDescending($flag)
    {
        $this->params['descending'] = $flag;
        return $this;
    }

    /**
     * The group option controls whether the reduce function reduces to a set of distinct keys or to a single result row.
     *
     * @param  bool $flag
     * @return Query
     */
    public function setGroup($flag)
    {
        $this->params['group'] = $flag;
        return $this;
    }

    public function setGroupLevel($level)
    {
        $this->params['group_level'] = $level;
        return $this;
    }

    /**
     * Use the reduce function of the view. It defaults to true, if a reduce function is defined and to false otherwise.
     *
     * @param  bool $flag
     * @return Query
     */
    public function setReduce($flag)
    {
        $this->params['reduce'] = $flag;
        return $this;
    }

    /**
     * Automatically fetch and include the document which emitted each view entry
     *
     * @param  bool $flag
     * @return Query
     */
    public function setIncludeDocs($flag)
    {
        $this->params['include_docs'] = $flag;
        return $this;
    }

    /**
     * Controls whether the endkey is included in the result. It defaults to true.
     *
     * @param  bool $flag
     * @return Query
     */
    public function setInclusiveEnd($flag)
    {
        $this->params['inclusive_end'] = $flag;
        return $this;
    }

    public function getParameter($key)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        return null;
    }

    /**
     * Create non existing view
     *
     * @return void
     */
    protected function createView()
    {
        if (!$this->doc) {
            throw new \Exception("No DesignDocument Class is connected to this view query, cannot create the design document with its corresponding view automatically!");
        }

        $response = $this->client->request(
            "PUT",
            sprintf(
                "/%s/_design/%s",
                $this->databaseName,
                $this->designDocumentName
            ),
            json_encode( array(
                '_id'   => '_design/' . $this->designDocumentName,
                'views' => $this->doc->getViews(),
            ) )
        );
    }
}