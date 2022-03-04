@servers(['prd' => '-A web-prd', 'stg' => '-A web-stg'])

@setup
    $repository = 'ssh://git@255.255.255.255:65535/sample_project.git';

    if($server === "prd"){
        $releases_dir = '/var/www/html/sample_project_prd/releases';
        $app_dir = '/var/www/html/sample_project_prd';
    }elseif ($server === "stg") {
        $releases_dir = '/var/www/html/sample_project_stg/releases';
        $app_dir = '/var/www/html/sample_project_stg';
    }

    $releases_dir = '/var/www/html/sample_project/releases';
    $app_dir = '/var/www/html/sample_project';
    $release = 'web_'.date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy_production', ['on' => 'prd'])
    clone_repository
    run_composer
    copy_unity_build_dir
    copy_setting_file
    update_symlinks
    clean_old_releases
@endstory

@story('deploy_staging', ['on' => 'stg'])
    clone_repository
    run_composer
    copy_unity_build_dir
    copy_setting_file
    update_symlinks
    clean_old_releases
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}

    @if ($branch)
        git clone -b {{ $branch }} {{ $repository }} {{ $new_release_dir }}
    @endif
    
    cd {{ $new_release_dir }}
    git reset --hard {{ $commit }}
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --no-dev
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current
@endtask
