<?php
namespace Chalcedonyt\QueryBuilderTemplate\Templates\Factory;
use Chalcedonyt\QueryBuilderTemplate\Templates\Template;
use Illuminate\Database\Query\JoinClause;
use DB;

abstract class AbstractTemplateFactory implements TemplateFactoryInterface
{
    /**
     * Sets up the QuerySpecificationsTemplate and returns it
     * @return QuerySpecificationsTemplateInterface $template
     */
    public function create(){
        $request = app(\Illuminate\Http\Request::class);

        //We need to limit the results so it don't go search 1 million rows
        $limit = $request->input('limit', 1000);

        if($limit) {
            $query = $this -> getBaseQuery()->limit($limit);
        }
        //0 means unlimited results, used on download excel on model search page
        else if ($limit == 0) {
            $query = $this -> getBaseQuery();
        }

        $template = new Template( $query );
        $template -> setAvailableJoinClauses( $this -> getAvailableJoinClauses() );
        return $template;
    }

    /**
     * The base query. DB::table and ::select should be set here
     * @return Builder
     */
    abstract protected function getBaseQuery();

    /**
     * Possible Joins to make depending on specifications passed.
     * @return Array of Illuminate\Database\Query\JoinClause
     */
    abstract protected function getAvailableJoinClauses();
}
?>
