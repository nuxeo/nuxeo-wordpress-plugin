node('SLAVE') {
    try {
        wrap([$class: 'TimestamperBuildWrapper']) {
            stage 'checkout'
            checkout([$class: 'GitSCM', branches: [[name: '*/master']], browser: [$class: 'GithubWeb', repoUrl: 'https://github.com/nuxeo/nuxeo-wordpress-plugin'], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'CloneOption', depth: 0, noTags: false, reference: '', shallow: false, timeout: 300]], submoduleCfg: [], userRemoteConfigs: [[url: 'git@github.com:nuxeo/nuxeo-wordpress-plugin.git']]])
            docker.image('quay.io/nuxeo/nuxeo-qaimage-php').pull()
            docker.build('nuxeo-qaimage-wordpress-plugin', 'docker/qa').inside {
                stage 'build'
                sh 'rm -rf plugin-wp-nuxeo/vendor && composer install'
                stage 'package'
                sh '''
VERSION=$(cat plugin-wp-nuxeo/nuxeo-plugin.php | awk \'/Version:/{print $2}\' | tr -d \'[[:space:]]\') && \
tar -czf plugin-wp-nuxeo-${VERSION}.tgz plugin-wp-nuxeo'''
                archive 'plugin-wp-nuxeo-*.tgz'
            }
        }
    } catch (e) {
        step([$class: 'ClaimPublisher'])
        throw e
    }
}