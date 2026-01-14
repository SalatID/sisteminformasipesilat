<div class="sidebar">
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="{{asset("assets")}}/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
      </div>
      <div class="info">
        <a href="#" class="d-block">{{auth()->user()->fullname}}</a>
        <small class="text-white">{{App\Models\User::mapRoleToText(auth()->user()->role)}}</small>
      </div>
    </div>

    <!-- SidebarSearch Form -->
    <div class="form-inline">
      <div class="input-group" data-widget="sidebar-search">
        <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
        <div class="input-group-append">
          <button class="btn btn-sidebar">
            <i class="fas fa-search fa-fw"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <!-- Add icons to the links using the .nav-icon class
             with font-awesome or any other icon font library -->
             <li class="nav-item">
              <a href="{{route('dashboard')}}" class="nav-link">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Dashboard
                </p>
              </a>
            </li>
             @foreach (\App\Http\Controllers\Controller::list_menu() as $item)
              @canany($item["permision"])
              <li class="nav-item">
                <a href="{{$item['src']!='#'?route($item['src']):'#'}}" class="nav-link">
                  <i class="nav-icon {{$item['icon']}}"></i>
                  <p>
                    {{$item['name']}}
                    @if (count($item["children"])>0)
                    <i class="right fas fa-angle-left"></i>
                    @endif
                  </p>
                </a>
                @if (count($item["children"])>0)
                <ul class="nav nav-treeview">
                  @foreach ($item["children"] as $child)
                      @canany($child["permision"])
                        <li class="nav-item">
                          <a href="{{$child['src']!='#'?route($child['src']):'#'}}" class="nav-link">
                            <i class="{{$child['icon']}} nav-icon"></i>
                            <p>{{$child['name']}}</p>
                          </a>
                        </li>
                      @endcanany
                  @endforeach
                </ul>
                @endif
              </li>
              @endcanany
             @endforeach
             <li class="nav-item">
              <a href="{{route('logout')}}" class="nav-link">
                <i class="nav-icon fas fa-arrow-right"></i>
                <p>
                  Logout
                </p>
              </a>
            </li>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>