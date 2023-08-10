<?php

namespace App\Modules\Dns\Controllers;

use App\Modules\Dns\Models\Record;
use App\Modules\Dns\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class DnsApiController
{
    public $ns1;
    public $ns2;
    public $soa_dom;
    public $slave;
    public $api_key = 'Jgh3Khsb2hFPKab2';

    public function __construct(Request $request)
    {
        if ($request->api_key !== $this->api_key) {
            die('api_key_wrong');
        }

        $this->ns1 = 'dns1.nencer.com';
        $this->ns2 = 'dns2.nencer.com';
        $this->slave = [
            'ip' => '127.0.0.1',
            'database' => 'db',
            'user' => 'root',
            'password' => 'root',
        ];
        $this->soa_dom = 'admin.nencer.com';
    }


    public function records($domain)
    {

        if (filter_var($domain, FILTER_VALIDATE_DOMAIN)) {

            $records = Record::where('domain', $domain)->get();
            if (count($records)) {
                return response()->json($records->toArray());
            }

        } else {
            return response()->json(['error' => '01', 'message' => 'domain_invalid', 'data' => null]);
        }

    }

    public function zones()
    {
        $zones = Zone::get();
        if (count($zones)) {
            return response()->json($zones->toArray());
        }
    }

    public function get_zone($domain)
    {

        if (filter_var($domain, FILTER_VALIDATE_DOMAIN)) {

            $zones = Zone::where('name', $domain)->with('records')->get();
            if (count($zones)) {

                return response()->json(['error' => '00', 'message' => 'success', 'data' => $zones->toArray()]);
            } else {
                return response()->json(['error' => '01', 'message' => 'zone_not_found', 'data' => null]);
            }

        } else {
            return response()->json(['error' => '01', 'message' => 'domain_invalid', 'data' => null]);
        }

    }


    public function zone_create(Request $request)
    {

        if (isset($request->domain)) {

            if (filter_var($request->domain, FILTER_VALIDATE_DOMAIN)) {

                $check = Zone::where('name', $request->domain)->first();
                if (!$check) {

                    DB::beginTransaction();
                    $zone = new Zone();
                    $zone->name = strtolower($request->domain);
                    $zone->type = strtoupper($request->type) ?? 'MASTER';
                    $zone->save();

                    //Tạo NS1
                    $ns1 = new Record();
                    $ns1->type = 'NS';
                    $ns1->content = $this->ns1;
                    $ns1->name = strtolower($request->domain);
                    $ns1->domain = strtolower($request->domain);
                    $ns1->domain_id = $zone->id;
                    $ns1->ttl = 3600;
                    $ns1->prio = 0;
                    $ns1->save();

                    //Tạo NS2
                    $ns1 = new Record();
                    $ns1->type = 'NS';
                    $ns1->content = $this->ns2;
                    $ns1->name = strtolower($request->domain);
                    $ns1->domain = strtolower($request->domain);
                    $ns1->domain_id = $zone->id;
                    $ns1->ttl = 3600;
                    $ns1->prio = 0;
                    $ns1->save();

                    //Tạo SOA
                    $ns1 = new Record();
                    $ns1->type = 'SOA';
                    $ns1->content = $this->ns1 . " " . $this->soa_dom . " " . Carbon::now()->format('Ymds') . " 10800 3600 604800 3600";
                    $ns1->name = strtolower($request->domain);
                    $ns1->domain = strtolower($request->domain);
                    $ns1->domain_id = $zone->id;
                    $ns1->ttl = 3600;
                    $ns1->prio = 0;
                    $ns1->save();
                    DB::commit();
                    return response()->json(['error' => '00', 'message' => 'success', 'data' => null]);
                } else {
                    return response()->json(['error' => '01', 'message' => 'domain_existed', 'data' => null]);
                }

            } else {
                return response()->json(['error' => '01', 'message' => 'domain_invalid', 'data' => null]);
            }

        } else {
            return response()->json(['error' => '01', 'message' => 'failed', 'data' => null]);
        }


    }


    public function del_zone($domain)
    {
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN)) {

            $zone = Zone::where('name', $domain)->first();
            if ($zone) {
                DB::beginTransaction();
                Record::where('domain_id', $zone->id)->delete();
                $zone->delete();
                DB::commit();

                return response()->json(['error' => '00', 'message' => 'success', 'data' => null]);

            } else {
                return response()->json(['error' => '01', 'message' => 'failed', 'data' => null]);
            }

        } else {
            return response()->json(['error' => '01', 'message' => 'domain_invalid', 'data' => null]);
        }
    }


    public function create_record(Request $request)
    {

        if (isset($request->domain) && isset($request->type) && isset($request->name) && isset($request->value)) {
            if (filter_var($request->domain, FILTER_VALIDATE_DOMAIN)) {

                $zone = Zone::where('name', $request->domain)->first();
                if ($zone) {

                    if(strpos($request->name, ".") <= 3){
                        if($request->name == "@"){
                            $name  = $zone->name;
                        }else{
                            $name = strtolower($request->name);
                        }

                        $record = Record::where('type', $request->type)->where('name', $name)->where('domain_id', $zone->id)->first();
                        if($record){

                            $record->content = strtolower($request->value);
                            $record->ttl = $request->ttl ?? 3600;
                            $record->prio = $request->prio ?? 0;
                            $record->update();

                            return response()->json(['error' => '00', 'message' => 'success', 'data' => $record->toArray()]);

                        }else{

                            $ns1 = new Record();
                            $ns1->type = strtoupper($request->type);
                            $ns1->content = strtolower($request->value);
                            $ns1->name = $name;
                            $ns1->domain = strtolower($zone->name);
                            $ns1->domain_id = $zone->id;
                            $ns1->ttl = $request->ttl ?? 3600;
                            $ns1->prio = $request->prio ?? 0;
                            $ns1->save();

                            return response()->json(['error' => '00', 'message' => 'success', 'data' => $ns1->toArray()]);

                        }

                    }else{
                        return response()->json(['error' => '01', 'message' => 'invalid_name', 'data' => null]);
                    }

                } else {
                    return response()->json(['error' => '01', 'message' => 'zone_not_existed', 'data' => null]);
                }

            } else {
                return response()->json(['error' => '01', 'message' => 'domain_invalid', 'data' => null]);
            }
        } else {
            return response()->json(['error' => '01', 'message' => 'invalid_post_data', 'data' => null]);
        }

    }

    public function del_record(Request $request)
    {

        if (isset($request->domain) && isset($request->type) && isset($request->name) && isset($request->value)) {
            if (filter_var($request->domain, FILTER_VALIDATE_DOMAIN)) {

                $zone = Zone::where('name', $request->domain)->first();
                if ($zone) {
                    if($request->name == "@"){
                        $name  = $zone->name;
                    }else{
                        $name = strtolower($request->name);
                    }

                    $record = Record::where('type', $request->type)->where('name', $name)->where('domain_id', $zone->id)->where('content', $request->value)->first();
                    if($record){

                        try{
                            $record->delete();
                            return response()->json(['error' => '00', 'message' => 'success', 'data' => null]);
                        }catch (\Exception $e){
                            return response()->json(['error' => '01', 'message' => 'del_failed', 'data' => null]);
                        }

                    }else{
                        return response()->json(['error' => '01', 'message' => 'record_not_existed', 'data' => null]);
                    }

                } else {
                    return response()->json(['error' => '01', 'message' => 'zone_not_existed', 'data' => null]);
                }

            } else {
                return response()->json(['error' => '01', 'message' => 'domain_invalid', 'data' => null]);
            }
        } else {
            return response()->json(['error' => '01', 'message' => 'invalid_post_data', 'data' => null]);
        }

    }



}
