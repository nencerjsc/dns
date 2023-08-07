<?php

namespace App\Modules\Dns\Controllers;
use App\Modules\Dns\Models\Record;
use App\Modules\Dns\Models\Zone;
use Illuminate\Http\Request;

class DnsApiController
{
    public function records($domain){

        if(filter_var($domain, FILTER_VALIDATE_DOMAIN)){

            $records = Record::where('domain', $domain)->get();
            if(count($records)){
                return response()->json($records->toArray());
            }

        }else{
            return 'domain_invalid';
        }

    }

    public function zones(){
        $zones = Zone::get();
        if(count($zones)){
            return response()->json($zones->toArray());
        }
    }

    public function get_zone($domain){

        if(filter_var($domain, FILTER_VALIDATE_DOMAIN)){

            $zones = Zone::where('name', $domain)->with('records')->get();
            if(count($zones)){
                return response()->json($zones->toArray());
            }

        }else{
            return 'domain_invalid';
        }

    }


    public function zone_create(Request $request){

        if(config('app.api_key') == $request->api_key){

            if(isset($request->domain)){

                if(filter_var($request->domain, FILTER_VALIDATE_DOMAIN)){

                    $check = Zone::where('name', $request->domain)->first();
                    if(!$check){

                        $zone = new Zone();
                        $zone->name = $request->domain;
                        $zone->type = $request->type ?? 'MASTER';
                        $zone->save();
                        return 'success';
                    }else{
                        return 'domain_existed';
                    }

                }else{
                    return 'domain_invalid';
                }

            }else{
                return 'failed';
            }
        }else{
            return 'failed';
        }

    }


}
