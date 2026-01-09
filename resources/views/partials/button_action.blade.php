@can($permision."_edit")
<a class="link mx-1" href="{{isset($src)?route($src.'.edit',[\Illuminate\Support\Facades\Crypt::encryptString($params??'')]):'#'}}" {{isset($target) ? 'data-target='.route($target.'.edit',[\Illuminate\Support\Facades\Crypt::encryptString($params??'')]).' data-id='.\Illuminate\Support\Facades\Crypt::encryptString($params??'').' onclick=editData(this) ':'' }}><i class="fas fa-edit text-primary"></i></a>
@endcan
@can($permision."_delete")
<a class="link mx-1" href="{{route(($src??($target??'')).'.destroy',[\Illuminate\Support\Facades\Crypt::encryptString($params??'')])}}" onclick="delete_confirmation(this,event)"><i class="fas fa-trash text-danger"></i></a>
@endcan
@can($permision."_show")
<a class="link mx-1" href="{{isset($src)?route($src.".show",[\Illuminate\Support\Facades\Crypt::encryptString($params??'')]):'#'}}" {{isset($target)? 'data-toggle="modal" data-target="#'.$target.'ModalShow"':'' }}><i class="fas fa-search text-success"></i></a>
@endcan
