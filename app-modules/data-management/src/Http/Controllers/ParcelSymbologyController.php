<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\DataManagement\Models\ParcelSymbology;
use Modules\DataManagement\Models\Submission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\DataManagement\Services\QueryService;
use Illuminate\Support\Str;

class ParcelSymbologyController
{
    protected $query;

    public function __construct(

        QueryService $query,
        )
    {
        $this->query = $query->getQuery();

    }


    public function index()
    {
 
       return ParcelSymbology::all();
    }

    /**
     * Get a specific saved query by ID
     */
    public function show($id)
    {
        $query = ParcelSymbology::where('project_id', $id)->get();
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Query not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $query
        ]);
    }

    /**
     * Save a new query
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|numeric',
            'query_structure' => 'required|array',
            'query_structure.operator' => 'required|in:AND,OR',
            'query_structure.rules' => 'required|array'
        ]);
        

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

  
        $query = ParcelSymbology::create([
            'name' => $request->name,
            'description' => $request->description,
            'project_id' => $request->project_id,
            'query_structure' => $request->query_structure,
            'created_by' => auth()->id()
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Query saved successfully',
            'data' => $query
        ], 201);
    }

    /**
     * Update an existing query
     */
    public function update(Request $request, $id)
    {
        $query = ParcelSymbology::find($id);
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Query not found'
            ], 404);
        }

        // Check if user owns the query
        if ($query->created_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this query'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'query_structure' => 'sometimes|required|array',
            'query_structure.operator' => 'required_with:query_structure|in:AND,OR',
            'query_structure.rules' => 'required_with:query_structure|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $query->update($request->only(['name', 'description', 'query_structure']));

        return response()->json([
            'success' => true,
            'message' => 'Query updated successfully',
            'data' => $query
        ]);
    }

    /**
     * Delete a saved query
     */
    public function destroy($id)
    {
        $query = ParcelSymbology::find($id);
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Query not found'
            ], 404);
        }

        // Check if user owns the query
        if ($query->created_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this query'
            ], 403);
        }

        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Query deleted successfully'
        ]);
    }

    /**
     * Execute a saved query
     */
    public function execute(Request $request, $id)
    {
        $ps = ParcelSymbology::find($id);

        $query = Submission::with($this->query)->with('extraAttributes');

        $query->whereHas('projects', function ($q) use ($ps) {
            $q->where('projects.id', $ps->project_id);
        });
      
        $this->getSearchData($query, $ps->query_structure);
        $data = $query->get();
        return count($data);
        
        

       
    }

    /**
     * Get all queries for a user (all projects)
     */
    public function userQueries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $perPage = $request->get('per_page', 20);
        
        $queries = ParcelSymbology::forUser(auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $queries->items(),
            'pagination' => [
                'current_page' => $queries->currentPage(),
                'per_page' => $queries->perPage(),
                'total' => $queries->total(),
                'last_page' => $queries->lastPage()
            ]
        ]);
    }


    public static function getSearchData($query, $filter)
    {
        // Check if it's a flat search
        if (isset($filter) && is_array($filter)) {
            self::buildNestedQuery($query, $filter);
            
        }
    }

    /**
     * Recursively build nested query conditions
     */
    private static function buildNestedQuery($query, $condition, $method = 'where')
    {
        if (isset($condition['operator']) && isset($condition['rules'])) {
            $operator = strtoupper($condition['operator']);
            
            $query->{$method}(function ($subQuery) use ($condition, $operator) {
                foreach ($condition['rules'] as $rule) {
                    if (isset($rule['operator']) && isset($rule['rules'])) {
                        // Nested condition group
                        self::buildNestedQuery($subQuery, $rule, $operator === 'AND' ? 'where' : 'orWhere');
                    } else {
                        // Single condition
                        $field = $rule['field'];
                        $value = $rule['value'];
                        $ruleOperator = $rule['operator'] ?? '=';
                        
                        if ($operator === 'AND') {
                            self::applyFilter($subQuery, $field, $value, $ruleOperator);
                        } else {
                            $subQuery->orWhere(function ($orSubQuery) use ($field, $value, $ruleOperator) {
                                self::applyFilter($orSubQuery, $field, $value, $ruleOperator);
                            });
                        }
                    }
                }
            });
        }
    }

    private static function applyFilter($query, $key, $value, $operator = '=')
    {
        if ($value === null || $value === '') {
            return;
        }
        
        if (Str::contains($key, '__')) {
            [$relation, $column] = explode('__', $key, 2);
            if ($relation === 'extra_attributes_json') {
                $query->whereHas('extraAttributes', function ($q) use ($column, $value, $operator) {
                    $q->where('attribute_name', $column)
                    ->where('attribute_value', $value);
                });
            } else {
                $query->whereHas($relation, function ($q) use ($column, $value, $operator) {
                    if ($operator === '=') {
                        $q->where($column, $value);
                    } else if ($operator === 'like') {
                        $q->where($column, 'like', '%' . $value . '%');
                    }
                    // Add more operators as needed
                });
            }
        } else {
            if ($operator === '=') {
                $query->where($key, $value);
            } else if ($operator === 'like') {
                $query->where($key, 'like', '%' . $value . '%');
            }
        }
    }

}
