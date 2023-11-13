<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\users\ReviewRequest;
use App\Models\User;
use App\Transformers\NotificationTransformer;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function notifications(Request $request)
    { 
        $skip = $request->skip? $request->skip :0;
        $take = $request->take? $request->take :10;
        $notifications = $request->user()->globalNotifications()
                                       ->latest()
                                       ->skip($skip)->take($take)
                                       ->get();
        $notifications = fractal()
        ->collection($notifications)
        ->transformWith(new NotificationTransformer('user_notifications'))
        ->toArray();
        return $this->dataResponse($notifications,'notifications',200);     

  }

  public function makeReview(ReviewRequest $request,$driver_id)
  {
        $data   = $request->validated();
        $driver = User::findOrFail($driver_id);
        $driver->reviews()->create([
         'user_id' => $request->user()->id,
         'rate'      =>$data['rate'],
         'comment'   =>$data['comment']
        ]);
        return $this->dataResponse(null,__('review is saved successfully'),200);

  }

  public function applyPromotion($promotion_id)
  {
       auth()->user()->promotions()->attach($promotion_id);
       return $this->dataResponse(null,__('promotion added successfully'),200);
  }

}
