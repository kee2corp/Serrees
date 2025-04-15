<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Constants;
use App\Models\FollowingList;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Like;
use App\Models\Media;
use App\Models\Property; 
use App\Models\PropertyTour;
use App\Models\PropertyType;
use App\Models\Reel;
use App\Models\Report;
use App\Models\SavedNotification;
use App\Models\Support;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function addUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'login_type' => 'required',
            'device_type' => 'required',
            'device_token' => 'required',
            
        ]);

         if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }


        $user = User::where('email', $request->email)->first();

        if ($user) {
            if ($request->has('user_type')) {
                $user->user_type = (int) $request->user_type;
            }
            $user->device_type = (int) $request->device_type;
            $user->device_token = $request->device_token;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User is already exist',
                'data' => $user,
'verified' => $user->verified,
            ]);
        }
        
        $user = new User();
        $user->fullname = $request->fullname;
        $user->email = $request->email;
        if ($request->has('user_type')) {
            $user->user_type = (int) $request->user_type;
        } else {
            $user->user_type = null;
        }
        $user->login_type = (int) $request->login_type;
        $user->device_type = (int) $request->device_type;
        $user->device_token = $request->device_token;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User Added Successfully',
            'data' => $user,
'verified' => $user->verified,
        ]);

    }

    public function editProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }


        $user = User::where('id', $request->user_id)->first();
        if ($user) { 
            if ($request->has('fullname')) {
                $user->fullname = $request->fullname;
            }
            if ($request->has('about')) {
                $user->about = $request->about;
            }
            if ($request->hasFile('profile')) {
                if ($user->profile != null) {
                    $path = GlobalFunction::deleteFile($user->profile);
                }
                $file = $request->file('profile');
                $path = GlobalFunction::saveFileAndGivePath($file);
                $user->profile = $path;
            }
            if ($request->has('address')) {
                $user->address = $request->address;
            }
            if ($request->has('block_user_ids')) {
                $user->block_user_ids = $request->block_user_ids;
            }
            if ($request->has('phone_office')) {
                $user->phone_office = $request->phone_office;
            }
            if ($request->has('mobile_no')) {
                $user->mobile_no = $request->mobile_no;
            }
            if ($request->has('fax')) {
                $user->fax = $request->fax;
            }
            if ($request->has('saved_property_ids')) {
                $user->saved_property_ids = $request->saved_property_ids;
            }
            if ($request->has('saved_reel_ids')) {
                $user->saved_reel_ids = $request->saved_reel_ids;
            }
            if ($request->has('user_type')) {
                $user->user_type = (int) $request->user_type;
            }
            if ($request->has('is_notification')) {
                $user->is_notification = (int) $request->is_notification;
            }
            // 'verification_status' is admin-controlled and cannot be edited here
            $user->save();

            //      if ( $user->user_type == Constants::customer ) { $user->user_type = "Customer"; }
            // else if ( $user->user_type == Constants::buyer ) { $user->user_type = "Buyer"; }
            // else if ( $user->user_type == Constants::owner ) { $user->user_type = "Owner"; }
            // else if ( $user->user_type == Constants::broker ) { $user->user_type = "Broker/Agent"; }

            return response()->json([
                'status' => true,
                'message' => 'User Updated Successfully',
                'data' => $user,
'verified' => $user->verified,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User Not Found',
            ]);
        }
    }

    public function fetchProfileDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'my_user_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }


        $user = User::where('id', $request->user_id)->first();
        if ($user == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }

        $forSaleProperty = Property::where('user_id', $request->user_id)->where('property_available_for', Constants::forSale)->count();
        $forRentProperty = Property::where('user_id', $request->user_id)->where('property_available_for', Constants::forRent)->count();


        $waitingTourRecivedRequest = PropertyTour::where('property_user_id', $request->user_id)->whereRelation('property', 'is_hidden', Constants::show)->where('tour_status', Constants::waitingTour)->count();
        $upcomingTourRecivedRequest = PropertyTour::where('property_user_id', $request->user_id)->whereRelation('property', 'is_hidden', Constants::show)->where('tour_status', Constants::confirmTour)->count();
        
        $waitingTourSubmittedRequest = PropertyTour::where('user_id', $request->user_id)->whereRelation('property', 'is_hidden', Constants::show)->where('tour_status', Constants::waitingTour)->count();
        $upcomingTourSubmittedRequest = PropertyTour::where('user_id', $request->user_id)->whereRelation('property', 'is_hidden', Constants::show)->where('tour_status', Constants::confirmTour)->count();

        $user->forSaleProperty = $forSaleProperty;
        $user->forRentProperty = $forRentProperty;
        $user->waitingTourRecivedRequest = $waitingTourRecivedRequest;
        $user->upcomingTourRecivedRequest = $upcomingTourRecivedRequest;
        $user->waitingTourSubmittedRequest = $waitingTourSubmittedRequest;
        $user->upcomingTourSubmittedRequest = $upcomingTourSubmittedRequest;

        // For Chat  
        $userProperty = Property::where('user_id', $request->user_id)->get()->pluck('id');
        $user->userPropertyIds = $userProperty;

        // userPropertyCount
        // $userProperty = Property::where('user_id', $request->user_id)->count();
        // $user->totalPropertiesCount = $userProperty;

        // $userReel = Reel::where('user_id', $request->user_id)->get()->pluck('id');
        $userReel = Reel::where('user_id', $request->user_id)->count();
        $user->totalReelsCount = $userReel;

        $followingStatus = FollowingList::whereRelation('user', 'is_block', 0)->where('user_id', $request->my_user_id)->where('my_user_id', $request->user_id)->first();
        $followingStatus2 = FollowingList::whereRelation('user', 'is_block', 0)->where('my_user_id', $request->my_user_id)->where('user_id', $request->user_id)->first();

        // koi ek bija ne follow nathi kartu to 0
        if ($followingStatus == null && $followingStatus2 == null) {
            $user->followingStatus = 0;
        }
        // same valo mane follow kar che to 1
        if ($followingStatus != null) {
            $user->followingStatus = 1;
        }
        // hu same vala ne follow karu chu to 2
        if ($followingStatus2) {
            $user->followingStatus = 2;
        }
        // banne ek bija ne follow kare to 3
        if ($followingStatus && $followingStatus2) {
            $user->followingStatus = 3;
        }

        $fetchSomeLatestReels = Reel::where('user_id', $request->my_user_id)
                                    ->with(['user', 'property', 'property.media'])
                                    ->orderBy('created_at', 'DESC')
                                    ->limit(5)
                                    ->get();
        foreach ($fetchSomeLatestReels as $fetchReel) {
            $isReelLike = Like::where('user_id', $request->my_user_id)->where('reel_id', $fetchReel->id)->first();
            $fetchReel->is_like = $isReelLike ? 1 : 0;

            $blockUserIds = User::where('is_block', 1)->pluck('id');

            $comments_count = Comment::whereNotIn('user_id', $blockUserIds)->where('reel_id', $fetchReel->id)->count();
            $likes_count = Like::whereNotIn('user_id', $blockUserIds)->where('reel_id', $fetchReel->id)->count();

            $fetchReel->comments_count = $comments_count;
            $fetchReel->likes_count = $likes_count;
        }
        
            
        $user->verified = $user->verified;
$user->yourReels = $fetchSomeLatestReels;

        return response()->json([
            'status' => true,
            'message' => 'Fetch user profile detail Successfully',
            'data' => $user,
'verified' => $user->verified,
        ]);
       
    }

    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }


        $user = User::where('id', $request->user_id)->first();
        if ($user) {
            $user->device_token = null;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User logout successfully'
            ]);
        } 
        return response()->json([
            'status' => false,
            'message' => 'User not found',
        ]);
    }

    public function deleteMyAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

         if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $user = User::where('id', $request->user_id)->first();
        if ($user == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }
       
        $properties = Property::where('user_id', $request->user_id)->get();
        if ($properties == null) {
            return response()->json([
                'status' => false,
                'message' => 'Property not found'
            ]);
        }

        foreach ($properties as $property) {
            $deleteMedias = Media::where('property_id', $property->id)->get();
            foreach ($deleteMedias as $deleteMedia) {
                GlobalFunction::deleteFile($deleteMedia->content);
                GlobalFunction::deleteFile($deleteMedia->thumbnail);
            }
            $deleteMedias->each->delete();

            PropertyTour::where('property_id', $property->id)->delete();

            Report::where('type', Constants::reportProperty)
                    ->where('item_id', $property->id)
                    ->delete();


            $reels = Reel::where('property_id', $property->id)->get();
            foreach ($reels as $reel) {
                Comment::where('reel_id', $reel->id)->delete();
                Like::where('reel_id', $reel->id)->delete();
                Report::where('item_id', $reel->id)
                        ->where('type', Constants::reportReel)
                        ->delete();
                GlobalFunction::deleteFile($reel->content);
                GlobalFunction::deleteFile($reel->thumbnail);
                SavedNotification::where('item_id', $reel->id)
                                ->whereIn('type', [Constants::notificationTypeReelLike, Constants::notificationTypeComment])
                                ->delete();
            }
            $reels->each->delete();
        }
        $properties->delete();

        Support::where('user_id', $request->user_id)->delete();
        PropertyTour::where('user_id', $request->user_id)->delete();
        Comment::where('user_id', $request->user_id)->delete();
        Like::where('user_id', $request->user_id)->delete();
        Report::where('type', Constants::reportUser)->where('item_id', $request->user_id)->delete();
        SavedNotification::where('my_user_id', $request->user_id)->delete();
        SavedNotification::where('user_id', $request->user_id)->delete();
        FollowingList::where('my_user_id', $request->user_id)->delete();
        FollowingList::where('user_id', $request->user_id)->delete();

        GlobalFunction::deleteFile($user->profile);
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User Account deleted successfully'
        ]);
        
    }

    public function users(Request $request)
    {
        return view('users');
    }

    public function userListWeb(Request $request)
    {
        $totalData = User::count();
        $rows = User::orderBy('id', 'DESC')->get();

        $result = $rows;

        $columns = [
            0 => 'id',
            1 => 'profile',
            2 => 'fullname',
        ];

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $totalFiltered = $totalData;
        if (empty($request->input('search.value'))) {
            $result = User::offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
        } else {
            $search = $request->input('search.value');
            $result = User::Where('fullname', 'LIKE', "%{$search}%")->orWhere('email', 'LIKE', "%{$search}%")
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            $totalFiltered = User::Where('fullname', 'LIKE', "%{$search}%")->orWhere('email', 'LIKE', "%{$search}%")->count();
        }
        $data = [];
        foreach ($result as $item) {
            
            $totalProperty = Property::where('user_id', $item->id)->count();

            $totalPropertyCount = '<span class="propertyCount">' . $totalProperty .'</span>';

            
            if ($item->profile == null) {
                $image = '<img src="asset/image/default.png" width="70" height="70" style="object-fit: cover;border-radius: 10px;box-shadow: 0px 10px 10px -8px #acacac;">';
            } else {
                $image = '<img src="' . $item->profile . '" width="70" height="70" style="object-fit: cover;border-radius: 10px;box-shadow: 0px 10px 10px -8px #acacac;">';
            }

            if ($item->device_type == 0) {
                $device_type = 'Android';
            } else {
                $device_type = 'iOS';
            }
 
            if ($item->is_block == 0) {
                $blockUser = '<a href="#" class="btn btn-danger px-4 text-white blockUserBtn" rel=' . $item->id . ' data-tooltip="Block User">' . __('<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg> <span class="ms-2"> Block </span>') . '</a>';
            } else {
                $blockUser = '<a href="#" class="btn btn-success px-4 text-white unblockUserBtn" rel=' . $item->id . ' data-tooltip="Unblock User">' . __('<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg> <span class="ms-2"> Unblock </span>') . '</a>';
            }


            $view = '<a href="usersDetail/' . $item->id . '" class="ms-3 btn btn-info px-3 text-white edit" rel=' . $item->id . ' data-tooltip="View User">' . __('<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> View') . '</a>';
            
            $action = '<span class="float-right">' . $blockUser  . $view . ' </span>';

            $data[] = [
                $image, 
                $item->fullname, 
                $item->email, 
                $device_type, 
                $totalPropertyCount, 
                $action
            ];
        }
        $json_data = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
        echo json_encode($json_data);
        exit();
    }

    public function usersDetail($id)
    {
        $user = User::where('id', $id)->first();
        $settings = GlobalSettings::first();
        return view('userDetails', [
            "settings" => $settings,
            'user' => $user,
        ]);
    }

    public function userPropertyList(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'Property Image',
            2 => 'Property Name',
            3 => 'Property Type',
            4 => 'Available for',
            5 => 'Build Year',
            6 => 'Price',
            7 => 'Featured',
        ];

        $limit = $request->input('length', 10); // Default length
        $start = $request->input('start', 0);
        $orderColumn = $columns[$request->input('order.0.column', 0)];
        $orderDir = $request->input('order.0.dir', 'asc');
        $search = $request->input('search.value', '');

        // Base query with eager loading for relationships
        $query = Property::where('user_id', $request->userId)
            ->with([
                'propertyType',
                'media' => function ($q) {
                    $q->where('media_type_id', Constants::overview);
                }
            ]);

        $totalData = Property::where('user_id', $request->userId)->count();

        // Apply search filter if search value is provided
        if (!empty($search)) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        $totalFiltered = $query->count();

        // Fetch data with pagination and ordering
        $result = $query->offset($start)
            ->limit($limit)
            ->orderBy($orderColumn, $orderDir)
            ->get();

        // Build the data array
        $data = $result->map(function ($item) {
            $firstContent = $item->media->pluck('content')->first();
            $imgContent = $firstContent 
                ? "<img src='{$firstContent}' class='tbl_img'>" 
                : "<img src='../asset/image/default.png' class='tbl_img'>";

            $propertyLink = url('propertyDetail', $item->id);

            $propertyTitle = "
                <div class='d-flex flex-column'>
                    <a href='{$propertyLink}' class='fs-6 fw-semibold'>{$item->title}</a>
                    <span class='w-100' style='white-space: break-spaces; color: #9b9b9b;'>{$item->address}</span>
                </div>";

            $availableFor = $item->property_available_for == 0
                ? "<span class='badge rounded bg-success text-white'>For Sale</span>"
                : "<span class='badge rounded bg-info text-white'>For Rent</span>";

            $featured = $item->is_featured == Constants::isFeatured
                ? "<label class='switch'><input type='checkbox' name='featured' rel='{$item->id}' value='{$item->is_featured}' class='featured' checked><span class='slider'></span></label>"
                : "<label class='switch'><input type='checkbox' name='featured' rel='{$item->id}' value='{$item->is_featured}' class='featured'><span class='slider'></span></label>";

            $price = "$ " . $item->first_price;

            $view = "<a href='{$propertyLink}' class='btn view-action-btn px-2' rel='{$item->id}'>
            <svg viewBox='0 0 24 24' width='24' height='24' stroke='currentColor' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round' class='css-i6dzq1'><path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'></path><circle cx='12' cy='12' r='3'></circle></svg>
            </a>";

            $edit = "<a href='#' class='edit btn btn-success px-2 ms-2' 
                    rel='{$item->id}'
                    data-title='{$item->title}'
                    data-bedrooms='{$item->bedrooms}'
                    data-bathrooms='{$item->bathrooms}'
                    data-area='{$item->area}'
                    data-about='{$item->about}'
                    data-address='{$item->address}'
                    data-society_name='{$item->society_name}'
                    data-built_year='{$item->built_year}'
                    data-furniture='{$item->furniture}'
                    data-total_floors='{$item->total_floors}'
                    data-floor_number='{$item->floor_number}'
                    data-car_parkings='{$item->car_parkings}'
                    data-maintenance_month='{$item->maintenance_month}'
                    data-property_available_for='{$item->property_available_for}'
                    data-first_price='{$item->first_price}'
                    data-second_price='{$item->second_price}'
                    >
                <svg data-name='Layer 1' height='200' id='Layer_1' viewBox='0 0 200 200' width='200' xmlns='http://www.w3.org/2000/svg'><title></title><path d='M170,70.5a10,10,0,0,0-10,10V140a20.06,20.06,0,0,1-20,20H60a20.06,20.06,0,0,1-20-20V60A20.06,20.06,0,0,1,60,40h59.5a10,10,0,0,0,0-20H60A40.12,40.12,0,0,0,20,60v80a40.12,40.12,0,0,0,40,40h80a40.12,40.12,0,0,0,40-40V80.5A10,10,0,0,0,170,70.5Zm-77,39a9.67,9.67,0,0,0,14,0L164.5,52a9.9,9.9,0,0,0-14-14L93,95.5A9.67,9.67,0,0,0,93,109.5Z' fill='#fff'></path></svg>
            </a>";
            $delete = "<a href='#' class='delete btn btn-danger px-2 ms-2' rel='{$item->id}'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-trash-2'>
                        <polyline points='3 6 5 6 21 6'></polyline>
                        <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path>
                        <line x1='10' y1='11' x2='10' y2='17'></line>
                        <line x1='14' y1='11' x2='14' y2='17'></line>
                    </svg>
                </a>";
            
            $action = "
                <span class='float-right d-flex'>
                    {$view}{$edit}{$delete}
                </span>";

            return [
                $imgContent,
                $propertyTitle,
                $item->propertyType->title ?? 'N/A',
                $availableFor,
                $item->built_year,
                $price,
                $featured,
                $action,
            ];
        });

        // Prepare and return JSON response
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ]);
    }

    public function userReelList(Request $request)
    {
        $totalData = Reel::where('user_id', $request->userId)->count();
        $rows = Reel::where('user_id', $request->userId)
                    ->orderBy('id', 'DESC')
                    ->get();

        $result = $rows;

        $columns = [
            0 => 'id',
        ];

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $totalFiltered = $totalData;
        if (empty($request->input('search.value'))) {
            $result = Reel::where('user_id', $request->userId)
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
        } else {
            $search = $request->input('search.value');
            $result = Reel::where('user_id', $request->userId)
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            $totalFiltered = $result->count();
        }
        $data = [];
        foreach ($result as $item) {

            $reelThumbnailUrl = $item->thumbnail ? $item->thumbnail : '../asset/image/default.png';

            $reelVideoBtn = '<div class="reel-column"><img src="' . $reelThumbnailUrl . '" class="tbl-img-thumbnail"><a href="javascript:;" rel="' . $item->id . '" data-description="' . $item->description . '" data-reel_video_url="' . $item->content . '" class="text-decoration-none btn-primary text-white reelVideoBtn px-3 py-2 border-radius d-flex align-items-center justify-content-center">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon></svg></a></div>';

            $propertyTitle = '<div class="d-flex flex-column">
                                <a href="../propertyDetail/' . $item->property->id . '" class="fs-6 fw-semibold">' . $item->property->title . '</a>
                                <span class="w-100" style="white-space: break-spaces; color: #9b9b9b;">' . $item->property->address . ' </span>    
                            </div>';

           

            $delete = '<a href="#" class="btn btn-danger px-2 text-white deleteReel" rel=' . $item->id . ' data-tooltip="Delete Property">' . __('<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>')  . '</a>';
            $action = '<span class="float-end">' . $delete . ' </span>';

            $data[] = [
                $reelVideoBtn,
                $propertyTitle,
                $item->likes_count,
                $item->comments_count,
                $item->views_count,
                $action
            ];

        }
        $json_data = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
        echo json_encode($json_data);
        exit();
    }

    public function updateFeatured(Request $request, $id)
    {
        $property = Property::where('id', $id)->first();
        if ($property) {
            $property->is_featured = $request->is_featured;
            $property->save();

            return response()->json([
                'status' => true,
                'message' => 'Featured item Updated Successfully',
            ]);
        } 
        return response()->json([
            'status' => false,
            'message' => 'Property not found',
        ]);
    }

    public function propertyDetail($id)
    {
        $property = Property::where('id', $id)->first();
        $medias = Media::where('property_id', $property->id)->get();
        $user = User::where('id', $property->user_id)->first();
        $propertiesCount = Property::where('user_id', $property->user_id)->get();
        $propertyType = PropertyType::where('id', $property->property_type_id)->first();
        $fetchAllPropertyTypes = PropertyType::get();
        $settings = GlobalSettings::first();

        if ($property) {
            return view('propertyDetail', [
                'property' => $property,
                'medias' => $medias,
                'user' => $user,
                'propertiesCount' => $propertiesCount,
                'propertyType' => $propertyType,
                'fetchAllPropertyTypes' => $fetchAllPropertyTypes,
                'settings' => $settings,
            ]);
        } 
    }

    public function support()
    {
         return view('support');
    }

    public function supportList(Request $request)
    {
        $totalData = Support::count();
        $rows = Support::orderBy('id', 'DESC')->get();

        $result = $rows;

        $columns = [
            0 => 'id',
            1 => 'user_id',
            2 => 'subject',
            3 => 'description',
        ];

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $totalFiltered = $totalData;
        if (empty($request->input('search.value'))) {
            $result = Support::offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');
            $result = Support::Where('subject', 'LIKE', "%{$search}%")->orWhere('description', 'LIKE', "%{$search}%")
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $totalFiltered = Support::Where('subject', 'LIKE', "%{$search}%")->orWhere('description', 'LIKE', "%{$search}%")->count();
        }
        $data = [];
        foreach ($result as $item) {

            $user = User::where('id', $item->user_id)->first();
            
            if ($user->profile == null) {
                $image = '<img src="asset/image/default.png" width="70" height="70" style="object-fit: cover;border-radius: 10px;box-shadow: 0px 10px 10px -8px #acacac;">';
            } else {
                $image = '<img src="' . $user->profile . '" width="70" height="70" style="object-fit: cover;border-radius: 10px;box-shadow: 0px 10px 10px -8px #acacac;">';
            }

            $description = '<span class="itemDescription">'.  $item->description .'</span>';

            $view = '<a href="#" class="ms-3 btn btn-danger px-2 text-white deleteSupport" rel=' . $item->id . '  data-tooltip="Delete Support" >' . __('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>') . '</a>';
            $action = '<span class="float-right">' . $view . ' </span>';

            $data[] = [
                $image, 
                $user->fullname, 
                $user->mobile_no, 
                $item->subject, 
                $description,
                $action
            ];
        }
        $json_data = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
        echo json_encode($json_data);
        exit();
    }

    public function deleteSupport(Request $request)
    {
        $support = Support::where('id', $request->support_id)->first();
        if ($support == null) {
            return response()->json([
                'status' => false,
                'message' => 'Report Not Found',
            ]);
        } else {
            $support->delete();
            return response()->json([
                'status' => true,
                'message' => 'Support Delete Successfully',
                'data' => $support
            ]);
        }
    }

    public function deleteThisProperty(Request $request)
    {
        $property = Property::where('id', $request->property_id)->first();
        if ($property == null) {
            return response()->json([
                'status' => false,
                'message' => 'Property not found',
            ]);
        }
        // $deleteMedias = Media::where('property_id', $request->property_id)->get();
        // foreach ($deleteMedias as $deleteMedia) {
        //     GlobalFunction::deleteFile($deleteMedia->content);
        // }
        // $deleteMedias->each->delete();

        // $deleteTour = propertyTour::where('property_id', $request->property_id)->get();
        // $deleteTour->each->delete();

        // $property->delete();

        $deleteMedias = Media::where('property_id', $property->id)->get();
        foreach ($deleteMedias as $deleteMedia) {
            GlobalFunction::deleteFile($deleteMedia->content);
            if ($deleteMedia->media_type_id == Constants::property_video) {
                GlobalFunction::deleteFile($deleteMedia->thumbnail);
            }
        }
        $deleteMedias->each->delete();

        PropertyTour::where('property_id', $property->id)->delete();
        Report::where('type', Constants::reportProperty)
            ->where('item_id', $property->id)
            ->delete();

        $reels = Reel::where('property_id', $property->id)->get();
        foreach ($reels as $reel) {
            Comment::where('reel_id', $request->reel_id)->delete();
            Like::where('reel_id', $request->reel_id)->delete();
            Report::where('item_id', $request->reel_id)
                ->where('type', Constants::reportReel)
                ->delete();
            GlobalFunction::deleteFile($reel->content);
            GlobalFunction::deleteFile($reel->thumbnail);
            SavedNotification::where('item_id', $request->reel_id)
                ->whereIn('type', [Constants::notificationTypeReelLike, Constants::notificationTypeComment])
                ->delete();
        }
        $reels->each->delete();

        $property->delete();

        return response()->json([
            'status' => true,
            'message' => 'Property delete successfully',
            'data' => $property,
        ]);
       
        
    }

    public function reports() 
    {
        return view('reports');
    }

    public function reportUser(Request $request) 
    {
        $user = User::where('id', $request->user_id)->first();

        if ($user == null) {
            return response()->json([
                'status' => false,
                'message' => 'User Not Found',
            ]);
        }

        if ($user->is_block == Constants::UserBlocked) {
            return response()->json([
                'status' => false,
                'message' => 'User is already in block list',
            ]);
        }
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'reason' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $reportType = Constants::reportUser;

        $report = new Report();
        $report->type = $reportType;
        $report->item_id = $request->user_id;
        $report->reason = $request->reason;
        $report->description = $request->description;
        $report->save();

        return response()->json([
            'status' => true,
            'message' => 'User Report Added Successfully',
            'data' => $report,
        ]);
    }

    public function userReportsList(Request $request)
    {
        $totalData = Report::where('type', Constants::reportUser)->count();
        $rows = Report::where('type', Constants::reportUser)->orderBy('id', 'DESC')->get();

        $result = $rows;

        $columns = [
            0 => 'id',
            1 => 'user_id',
            2 => 'reason',
            3 => 'desc',
        ];

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $totalFiltered = $totalData;
        if (empty($request->input('search.value'))) {
            $result = Report::where('type', Constants::reportUser)
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
        } else {
            $search = $request->input('search.value');
            $result = Report::where('type', Constants::reportUser)
                            ->where('reason', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%")
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            $totalFiltered = $result->count();
        }
        $data = [];
        foreach ($result as $item) {

            $user = User::where('id', $item->item_id)->first();
            
            if ($user->profile == null) {
                $image = '<img src="asset/image/default.png" width="70" height="70" style="object-fit: cover;border-radius: 10px;box-shadow: 0px 10px 10px -8px #acacac;">';
            } else {
                $image = '<img src="' . $user->profile . '" width="70" height="70" style="object-fit: cover;border-radius: 10px;box-shadow: 0px 10px 10px -8px #acacac;">';
            }

            $userName = '<a href="./usersDetail/' . $user->id . '">' . $user->fullname . '</a>';
            $itemDescription = '<span class="itemDescription">'. $item->description .'</span>';


            $block = '<a href="#" class="ms-3 btn btn-danger px-3 text-white blockUserBtn" rel=' . $item->id . ' data-tooltip="Block User" >' . __('<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg> Block User')  . '</a>';
            $rejectReport = '<a href="#" class="ms-3 btn btn-warning px-3 text-white rejectReportBtn" rel=' . $item->id . ' data-tooltip="Reject" >' . __('<svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg> Reject')  . '</a>';
            $action = '<span class="float-right">' . $rejectReport . $block . ' </span>';

            $data[] = [
                $image,
                $userName,  
                $item->reason,
                $itemDescription,
                $action
            ];
        }
        $json_data = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
        echo json_encode($json_data);
        exit();
    }

    public function rejectUserReport(Request $request)
    {
        $report = Report::where('id', $request->report_id)->first();
        if ($report == null) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
            ]);
        }

        $userReports = Report::where('item_id', $report->item_id)
                            ->where('type', Constants::reportUser)
                            ->get();
                            
        $userReports->each->delete();

        return response()->json([
            'status' => true,
            'message' => 'Report rejected successfully',
        ]);
        
    }

    function blockUserFromReport(Request $request) 
    {

        $report = Report::where('id', $request->report_id)->where('type', Constants::reportUser)->first();
        if ($report == null) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
            ]);
        }

        $user = User::where('id', $report->item_id)->first();
        if ($user == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }

        $user->is_block = Constants::UserBlocked;
        $user->save();

        Report::where('item_id', $report->item_id)
                ->where('type', Constants::reportUser)
                ->delete();

        return response()->json([
            'status' => true,
            'message' => 'User Added in Block list',
            'data' => $user,
'verified' => $user->verified,
        ]);
    
    }

    public function propertyReportsList(Request $request)
    {
        $totalData = Report::where('type', Constants::reportProperty)->count();
        $rows = Report::where('type', Constants::reportProperty)->orderBy('id', 'DESC')->get();

        $result = $rows;

        $columns = [
            0 => 'id',
            1 => 'user_id',
            2 => 'reason',
            3 => 'desc',
        ];

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $totalFiltered = $totalData;
        if (empty($request->input('search.value'))) {
            $result = Report::where('type', Constants::reportProperty)
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
        } else {
            $search = $request->input('search.value');
            $result = Report::where('type', Constants::reportProperty)
                            ->where('reason', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%")
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            $totalFiltered = $result->count();
        }
        $data = [];
        foreach ($result as $item) {

            $medias = Media::where('property_id', $item->item_id)->where('media_type_id', '!=', Constants::property_video)->where('media_type_id', Constants::overview)->get();
            $firstContent = $medias->pluck('content')->first();

            if ($firstContent != null) {
                $imgContent = "<img src=" . $firstContent . " class='tbl_img'>";
            } else {
                $imgContent = "<img src='./asset/image/default.png' class='tbl_img'>";
            }

            $propertyTitle = '<div class="d-flex flex-column">
                                <a href="./propertyDetail/'. $item->property->id . '" target="_blank" class="fs-6 fw-semibold">'. $item->property->title . '</a>
                                <span class="w-100" style="white-space: break-spaces; color: #9b9b9b;">' . $item->property->address . ' </span>    
                            </div>';

            $reasonDescription = '<span class="itemDescription">' . $item->reason . '</span>';
            $itemDescription = '<span class="itemDescription">' . $item->description . '</span>';


            $delete = '<a href="#" class="ms-3 btn btn-danger px-3 text-white deletePropertyFromReport" rel=' . $item->id . ' data-tooltip="Delete Property">' . __('<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Delete Property')  . '</a>';
            $rejectReport = '<a href="#" class="ms-3 btn btn-warning px-3 text-white rejectReportBtn" rel=' . $item->id . ' data-tooltip="Reject" >' . __('<svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg> Reject')  . '</a>';
            $action = '<span class="float-right">' . $rejectReport . $delete . ' </span>';

            $data[] = [
                $imgContent,
                $propertyTitle,
                $reasonDescription,
                $itemDescription,
                $action
            ];
        }
        $json_data = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
        echo json_encode($json_data);
        exit();
    }

    public function rejectPropertyReport(Request $request)
    {
        $report = Report::where('id', $request->report_id)->first();
        if ($report == null) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
            ]);
        }

        $propertyReports = Report::where('item_id', $report->item_id)
                            ->where('type', Constants::reportProperty)
                            ->get();

        $propertyReports->each->delete();

        return response()->json([
            'status' => true,
            'message' => 'Report rejected successfully',
        ]);
    }

    public function deletePropertyFromReport(Request $request)
    {
        $report = Report::where('id', $request->report_id)->first();
        if ($report == null) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
            ]);
        }

        $propertyId = $report->item_id;

        $property = Property::where('id', $propertyId)->first();
        if ($property == null) {
            return response()->json([
                'status' => false,
                'message' => 'Property not found',
            ]);
        }

        $deleteMedias = Media::where('property_id', $propertyId)->get();
        foreach ($deleteMedias as $deleteMedia) {
            GlobalFunction::deleteFile($deleteMedia->content);
            GlobalFunction::deleteFile($deleteMedia->thumbnail);
        }
        $deleteMedias->each->delete();

        $deleteTour = propertyTour::where('property_id', $propertyId)->get();
        $deleteTour->each->delete();

        $reels = Reel::where('property_id', $propertyId)->get();
        foreach ($reels as $reel) {

            Comment::where('reel_id', $reel->id)->delete();
            Like::where('reel_id', $reel->id)->delete();
            Report::where('item_id', $reel->id)
                    ->where('type', Constants::reportReel)
                    ->delete();
            GlobalFunction::deleteFile($reel->content);
            GlobalFunction::deleteFile($reel->thumbnail);
            
            SavedNotification::where('item_id', $request->reel_id)
                                ->whereIn('type', [Constants::notificationTypeReelLike, Constants::notificationTypeComment])
                                ->delete();
        }
        $reels->delete();

        Report::where('item_id', $report->item_id)
            ->where('type', Constants::reportProperty)
            ->delete();

        $property->delete();

        return response()->json([
            'status' => true,
            'message' => 'Property delete successfully',
        ]);
    }

    public function reelReportsList(Request $request)
    {
        $totalData = Report::where('type', Constants::reportReel)->count();
        $rows = Report::where('type', Constants::reportReel)->orderBy('id', 'DESC')->get();

        $result = $rows;

        $columns = [
            0 => 'id',
            1 => 'user_id',
            2 => 'reason',
            3 => 'desc',
        ];

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $totalFiltered = $totalData;
        if (empty($request->input('search.value'))) {
            $result = Report::where('type', Constants::reportReel)
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
        } else {
            $search = $request->input('search.value');
            $result = Report::where('type', Constants::reportReel)
                            ->where('reason', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%")
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            $totalFiltered = $result->count();
        }
        $data = [];
        foreach ($result as $item) {

            $reel = Reel::where('id', $item->item_id)->first();

            $reelVideoBtn = '<a href="javascript:;" rel="' . $item->id . '" data-description="'. $item->reel->description .'" data-reel_video_url="' . $reel->content . '" class="me-2 text-decoration-none btn-primary text-white reelVideoBtn px-3 py-2 border-radius d-flex align-items-center justify-content-center">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-1 "><circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon></svg>
            Reel</a>';

            $propertyTitle = '<div class="d-flex flex-column">
                                <a href="./propertyDetail/'. $item->reel->property->id . '" target="_blank" class="fs-6 fw-semibold">'. $item->reel->property->title . '</a>
                                <span class="w-100" style="white-space: break-spaces; color: #9b9b9b;">' . $item->reel->property->address . ' </span>    
                            </div>';

            $reasonDescription = '<span class="itemDescription">' . $item->reason . '</span>';
            $itemDescription = '<span class="itemDescription">' . $item->description . '</span>';


            $delete = '<a href="#" class="ms-3 btn btn-danger px-3 text-white deletePropertyFromReport" rel=' . $item->id . ' data-tooltip="Delete Property">' . __('<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Delete Reel')  . '</a>';
            $rejectReport = '<a href="#" class="ms-3 btn btn-warning px-3 text-white rejectReportBtn" rel=' . $item->id . ' data-tooltip="Reject" >' . __('<svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 me-2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg> Reject')  . '</a>';
            $action = '<span class="float-right">' . $rejectReport . $delete . ' </span>';

            $data[] = [
                $reelVideoBtn,
                $propertyTitle,
                $reasonDescription,
                $itemDescription,
                $action
            ];
        }
        $json_data = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
        echo json_encode($json_data);
        exit();
    }

    public function rejectReelReport(Request $request)
    {
        $report = Report::where('id', $request->report_id)->first();
        if ($report == null) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
            ]);
        }

        $reelReports = Report::where('item_id', $report->item_id)
                            ->where('type', Constants::reportReel)
                            ->get();

        $reelReports->each->delete();

        return response()->json([
            'status' => true,
            'message' => 'Report rejected successfully',
        ]);
    }

    public function deleteReelFromReport(Request $request)
    {
        $report = Report::where('id', $request->report_id)->first();
        if ($report == null) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
            ]);
        }

        $reelId = $report->item_id;

        $reel = Reel::where('id', $reelId)->first();
        if ($reel == null) {
            return response()->json([
                'status' => false,
                'message' => 'Reel not found',
            ]);
        }

        Comment::where('reel_id', $reelId)->delete();
        Like::where('reel_id', $reelId)->delete();
        Report::where('item_id', $reelId)
                ->where('type', Constants::reportReel)
                ->delete();
        GlobalFunction::deleteFile($reel->content);
        GlobalFunction::deleteFile($reel->thumbnail);
        SavedNotification::where('item_id', $reelId)
                            ->whereIn('type', [Constants::notificationTypeReelLike, Constants::notificationTypeComment])
                            ->delete();
        $reel->delete();

        return response()->json([
            'status' => true,
            'message' => 'Reel delete successfully',
        ]);
    }

    public function blockUserByAdmin(Request $request)
    {

        $user = User::where('id', $request->user_id)->first();

        if ($user) {
            $user->is_block = Constants::UserBlocked;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'User Added in Block list',
                'data' => $user,
'verified' => $user->verified,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }
    }

    public function unblockUserByAdmin(Request $request)
    {

        $user = User::where('id', $request->user_id)->first();

        if ($user) {
            $user->is_block = Constants::UserUnblocked;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'User Removed from Block list',
                'data' => $user,
'verified' => $user->verified,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }
    }

    public function fetchBlockUserList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'my_user_id' => 'required',
        ]);

         if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }


        $user = User::where('id', $request->my_user_id)->first();

        if ($user) {
            $blockUserIds = explode(',', $user->block_user_ids);
            $blockedUser = User::whereIn('id', $blockUserIds)->get();

            return response()->json([
                'status' => true,
                'message' => 'Fetching block user list',
                'data' => $blockedUser,
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'User not found'
        ]);        
    }

    public function userBlockFromApi(Request $request)
    {
        $user = User::where('id', $request->my_user_id)->first();

        if ($user) {
            $user->is_block = Constants::UserBlocked;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'User Added in Block list',
                'data' => $user,
'verified' => $user->verified,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }
    }

    public function followUser(Request $request)
    {
        $fromUser = User::where('id', $request->my_user_id)->first();
        $toUser = User::where('id', $request->user_id)->first();

        if ($fromUser && $toUser) {
            if ($fromUser == $toUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lol You did not follow yourself',
                ]);
            } else {
                $followingList = FollowingList::where('my_user_id', $request->my_user_id)->where('user_id', $request->user_id)->first();
                if ($followingList) {
                    return response()->json([
                        'status' => false,
                        'message' => 'User is Already in following list',
                    ]);
                } else {

                    $blockUserIds = explode(',', $fromUser->block_user_ids);

                    foreach ($blockUserIds as $blockUserId) {
                        if ($blockUserId == $request->user_id) {
                            return response()->json([
                                'status' => false,
                                'message' => 'You blocked this User',
                            ]);
                        }
                    }

                    $following = new FollowingList();
                    $following->my_user_id = (int) $request->my_user_id;
                    $following->user_id = (int) $request->user_id;
                    $following->save();

                    $followingCount = User::where('id', $request->my_user_id)->first();
                    $followingCount->following += 1;
                    $followingCount->save();

                    $followersCount = User::where('id', $request->user_id)->first();
                    $followersCount->followers += 1;
                    $followersCount->save();

                    $notificationDesc = $fromUser->fullname . ' has stared following you.';

                    if ($toUser->id != $fromUser->id) {
                        if ($toUser->is_notification == 1) {
                            GlobalFunction::sendPushNotificationToUser($notificationDesc, $toUser->device_token, $toUser->device_type);
                        }
                    }

                    $following->user = $fromUser;

                    $type = Constants::notificationTypeFollow;

                    $savedNotification = new SavedNotification();
                    $savedNotification->my_user_id = (int) $request->my_user_id;
                    $savedNotification->user_id = (int) $request->user_id;
                    $savedNotification->item_id = (int) $request->user_id;
                    $savedNotification->message = $notificationDesc;
                    $savedNotification->type = $type;
                    $savedNotification->save();

                    return response()->json([
                        'status' => true,
                        'message' => 'User Added in Following List',
                        'data' => $following,
                    ]);
                }
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User Not Found',
            ]);
        }
    }

    public function fetchFollowingList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'start' => 'required',
            'limit' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $user = User::where('id', $request->user_id)->first();
        if ($user == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }
        $blockUserIds = explode(',', $user->block_user_ids);

        $fetchFollowingList = FollowingList::whereRelation('user', 'is_block', 0)
                                ->whereNotIn('user_id', $blockUserIds)
                                ->where('my_user_id', $request->user_id)
                                ->with('user')
                                ->offset($request->start)
                                ->limit($request->limit)
                                ->get()
                                ->pluck('user');

        return response()->json([
            'status' => true,
            'message' => 'Fetch Following List',
            'data' => $fetchFollowingList,
        ]);
    }

    public function fetchFollowersList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'start' => 'required',
            'limit' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $user = User::where('id', $request->user_id)->first();
        if ($user == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }
        $fetchFollowersList = FollowingList::where('user_id', $request->user_id)
                                            ->with('followerUser')
                                            ->whereRelation('followerUser', 'is_block', 0)
                                            ->offset($request->start)
                                            ->limit($request->limit)
                                            ->get()
                                            ->pluck('followerUser');
        return response()->json([
            'status' => true,
            'message' => 'Fetch Followers List',
            'data' => $fetchFollowersList,
        ]);
    }

    public function unfollowUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'my_user_id' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }


        $fromUserQuery = User::query();
        $toUserQuery = User::query();

        $fromUser = $fromUserQuery->where('id', $request->my_user_id)->first();
        $toUser = $toUserQuery->where('id', $request->user_id)->first();

        if ($fromUser && $toUser) {
            if ($fromUser == $toUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lol You did not Remove yourself, Bcz You dont follow yourself',
                ]);
            } else {
                $followingList = FollowingList::where('my_user_id', $request->my_user_id)->where('user_id', $request->user_id)->first();
                if ($followingList) {
                    $followingCount = $fromUserQuery->where('id', $request->my_user_id)->first();
                    $followingCount->following -= 1;
                    $followingCount->save();

                    $followersCount = $toUserQuery->where('id', $request->user_id)->first();
                    $followersCount->followers -= 1;
                    $followersCount->save();

                    SavedNotification::where('my_user_id', $request->my_user_id)
                                    ->where('item_id', $request->user_id)
                                    ->where('type', Constants::notificationTypeFollow)
                                    ->delete();

                    $followingList->delete();

                    return response()->json([
                        'status' => true,
                        'message' => 'Unfollow user',
                        'data' => $followingList,
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'User Not Found',
                    ]);
                }
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User Not Found',
            ]);
        }
    }

    public function UserBlockedByUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'my_user_id' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $fromUser = User::where('id', $request->my_user_id)->first();
        if ($fromUser == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }

        $toUser = User::where('id', $request->user_id)->first();
        if ($toUser == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }

        $fetchFollowingUsers = FollowingList::where('my_user_id', $request->my_user_id)->where('user_id', $request->user_id)->first();
        if ($fetchFollowingUsers != null) {
            $followingCount = User::where('id', $request->my_user_id)->first();
            $followingCount->following -= 1;
            $followingCount->save();

            $followersCount = User::where('id', $request->user_id)->first();
            $followersCount->followers -= 1;
            $followersCount->save();

            $fetchFollowingUsers->delete();
        }


        $fetchFollowerUsers = FollowingList::where('user_id', $request->my_user_id)->where('my_user_id', $request->user_id)->first();
        if ($fetchFollowerUsers != null) {
            $followersCount = User::where('id', $request->my_user_id)->first();
            $followersCount->followers -= 1;
            $followersCount->save();

            $followingCount = User::where('id', $request->user_id)->first();
            $followingCount->following -= 1;
            $followingCount->save();

            $fetchFollowerUsers->delete();
        }

        $blockUserIds = explode(',', $fromUser->block_user_ids);
        foreach ($blockUserIds as $blockUserId) {
            if ($blockUserId == $request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'User already Blocked'
                ]);
            }
        }

        $fromUser->block_user_ids = $fromUser->block_user_ids . $request->user_id . ',';
        $fromUser->save();

        $userNotification = SavedNotification::where('my_user_id', $request->my_user_id)
                                                ->where('type', Constants::notificationTypeFollow)
                                                ->get();
        $userNotification->each->delete();

        return response()->json([
            'status' => true,
            'message' => 'User Block Successfully',
            'data' => $toUser
        ]);
    }

    public function UserUnblockedByUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'my_user_id' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $fromUser = User::where('id', $request->my_user_id)->first();
        if ($fromUser == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }

        $toUser = User::where('id', $request->user_id)->first();
        if ($toUser == null) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }

        $blockUserIds = explode(',', $fromUser->block_user_ids);
        foreach (array_keys($blockUserIds, $request->user_id) as $key) {
            unset($blockUserIds[$key]);
        }
        $fromUser->block_user_ids = implode(",", $blockUserIds);
        $fromUser->save();

        return response()->json([
            'status' => true,
            'message' => 'User Unblock Successfully',
            'data' => $toUser
        ]);
    }


public function updateVerificationStatus(Request $request, $id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json(['status' => false, 'message' => 'User not found']);
    }

    $user->verification_status = $request->verification_status;
    $user->verified = $request->verification_status; 
    $user->save();

    return response()->json(['status' => true, 'message' => 'Verification status updated']);
}



}