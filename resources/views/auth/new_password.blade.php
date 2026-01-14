
@extends('layout.index_auth')
@section('title',$title)
@section('content')
<div class="card-body login-card-body">
  @if ($errors->has('error'))
    <div class="alert alert-{{$errors->first('error')?'danger':'success'}} alert-dismissible">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
      <h5><i class="icon fas fa-{{$errors->first('error')?'ban':'check'}}"></i> {{$errors->first('error')?'Error':'Sucess'}}</h5>
     {{$errors->first('message') }}
    </div>
    @endif
    <p class="login-box-msg">{{$title}}</p>
    <form action="{{route('newpassword')}}" method="post">
      @csrf
      <input type="hidden" name="validator" value="{{$email}}">
      <input type="hidden" name="from" value="{{$from}}">
      <div class="form-group">
        <label for="inputPassword">Password</label>
        <div class="input-group">
          <input type="password" id="password" name="password" class="form-control {{$errors->has('password')?'is-invalid':''}}" placeholder="Password" aria-describedby="inputPassword-error" aria-invalid="true">
          <div class="input-group-append">
            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('password', 'togglePassword1')">
              <i class="fas fa-eye" id="togglePassword1"></i>
            </span>
          </div>
          @if($errors->has('password'))
              <span id="inputPassword-error" class="error invalid-feedback d-block">{{ $errors->first('password') }}</span>
          @endif
        </div>
      </div>
      <div class="form-group">
        <label for="inputRetypePassword">Retype Password</label>
        <div class="input-group">
          <input type="password" id="retype_password" name="retype_password" class="form-control {{$errors->has('retype_password')?'is-invalid':''}}" placeholder="Retype Password" aria-describedby="inputRetypePassword-error" aria-invalid="true">
          <div class="input-group-append">
            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('retype_password', 'togglePassword2')">
              <i class="fas fa-eye" id="togglePassword2"></i>
            </span>
          </div>
          @if($errors->has('retype_password'))
              <span id="inputRetypePassword-error" class="error invalid-feedback d-block">{{ $errors->first('retype_password') }}</span>
          @endif
        </div>
      </div>
      <div class="row">
        <!-- /.col -->
        <div class="col-4">
          <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </div>
        <!-- /.col -->
      </div>
    </form>
  </div>
@endsection