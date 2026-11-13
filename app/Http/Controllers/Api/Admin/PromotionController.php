<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\admins\PromotionRequest;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\User;
use App\Jobs\SendFirebaseNotification;
use App\Transformers\PromotionTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function index()
    {
      $promotions = Promotion::get();

      $promotions = fractal()
          ->collection($promotions)
          ->transformWith(new PromotionTransformer('dashboard'))
          ->toArray();

      return $this->dataResponse($promotions, 'promotions', 200);
    }

    public function store(PromotionRequest $request)
    {
       $data      = $request->validated();
       $code = rand(11111,99999);
       DB::beginTransaction();
       $promotion = Promotion::create([
        'expire_date'=> $data['expire_date'],
        'code'       => $code,
        'discount'   => $data['discount']
       ]);
      $notification =  Notification::create([
         'ar' =>['title'=>' خصم '.$promotion->discount.'على الرحلة القادمة %','description'=> ' صالح حتى'.$promotion->expire_date],
         'en' =>['title'=>'discount '.$promotion->discount.'% on the incoming drive','description'=>'valid till '.$promotion->expire_date],
         'promotion_id' => $promotion->id
       ]);
      $users_ids = User::whereHas('roles',function($q){
        return $q->where('name','user');
       })->where('notify_status',1)->where('is_active_phone',1)->orWhere('is_active_email',1)->pluck('id')->toArray();

      $notification->users()->attach($users_ids);
      if(count($users_ids) > 0)
      {
          $data = [
             'title'      => $notification->title,
             'body'       => $notification->description,
             'action_id'  => $promotion->id,
             'action_type'=> 'new-promotion'
          ];
         SendFirebaseNotification::dispatch($users_ids, $data, 'android')->afterCommit();
         $data['promotion_id'] = $promotion->id;
         unset($data['action_id']);
         SendFirebaseNotification::dispatch($users_ids, $data, 'ios')->afterCommit();
      }
      DB::commit();
      return $this->dataResponse(null,__('promotion stored successfully'),200);
    }

    public function update(PromotionRequest $request,$id)
    {
      $data = $request->validated();

      $promotion = Promotion::findOrFail($id);

      $updated = $promotion->update([
         'expire_date'=> $data['expire_date'],
         'discount'   => $data['discount']
       ]);
      if($updated)
      {
          return $this->dataResponse(null,__('updated successfully'),200);
      }
          return $this->dataResponse(null,__('failed to update'),500);

    }

    public function destroy(string $id)
    {
        //
        $promotion = Promotion::findOrFail($id);

        if($promotion->delete()){

            return $this->dataResponse(null,__('deleted successfully'),200);
        }
        return $this->dataResponse(null,__('failed to delete'),500);

    }


}
