'use strict';

/* global angular:false */
/* exported AppstoreController */

// create main application module
var app = angular.module('Application', []);

app.controller('AppstoreController', ['$scope', function ($scope) {
    $scope.ready = false;
    $scope.allApps = [];
    $scope.apps = null;
    $scope.app = null;
    $scope.searchString = '';
    $scope.cachedCategory = '';
    $scope.category = window.location.hash.slice(1);
    $scope.categoryList = [
        { tag: 'analytics',  icon: 'fas fa-chart-bar',       label: 'Analytics' },
        { tag: 'automation', icon: 'fas fa-robot',           label: 'Automation' },
        { tag: 'blog',       icon: 'fas fa-font',            label: 'Blog' },
        { tag: 'chat',       icon: 'fas fa-comments',        label: 'Chat' },
        { tag: 'git',        icon: 'fas fa-code-branch',     label: 'Code Hosting' },
        { tag: 'CRM',        icon: 'fab fa-connectdevelop',  label: 'CRM' },
        { tag: 'document',   icon: 'fas fa-file-word',       label: 'Documents' },
        { tag: 'email',      icon: 'fas fa-envelope-open',   label: 'Email' },
        { tag: 'federated',  icon: 'fas fa-project-diagram', label: 'Federated' },
        { tag: 'sync',       icon: 'fas fa-sync',            label: 'File Sync' },
        { tag: 'finance',    icon: 'fas fa-dollar-sign',     label: 'Finance' },
        { tag: 'forum',      icon: 'fas fa-users',           label: 'Forum' },
        { tag: 'gallery',    icon: 'fas fa-images',          label: 'Gallery' },
        { tag: 'game',       icon: 'fas fa-gamepad',         label: 'Games' },
        { tag: 'no-code',    icon: 'fas fa-code',            label: 'No-code' },
        { tag: 'notes',      icon: 'fas fa-sticky-note',     label: 'Notes' },
        { tag: 'project',    icon: 'fas fa-chart-line',      label: 'Project Management' },
        { tag: 'voip',       icon: 'fas fa-headset',         label: 'VoIP' },
        { tag: 'vpn',        icon: 'fas fa-user-secret',     label: 'VPN' },
        { tag: 'hosting',    icon: 'fas fa-bars',            label: 'Web Hosting' },
        { tag: 'wiki',       icon: 'fab fa-wikipedia-w',     label: 'Wiki' }
    ];

    $scope.showCategory = function (category) {
        if (category) window.location.href = '#' + category;
        else window.location.href = '#';

        window.scrollTo(0,0);
    };

    var searchTrackTimeout = null;

    $scope.filterApps = function () {
        $scope.app = null;

        var token = $scope.searchString ? $scope.searchString.toUpperCase() : $scope.category.toUpperCase();

        // clear the category if we search all apps
        if ($scope.searchString) $scope.category = '';

        $scope.apps = $scope.allApps.filter(function (app) {
            // only look in all content if we search, not category
            if ($scope.searchString) {
                if (app.manifest.id.toUpperCase().indexOf(token) !== -1) return true;
                if (app.manifest.title.toUpperCase().indexOf(token) !== -1) return true;
                if (app.manifest.tagline.toUpperCase().indexOf(token) !== -1) return true;
                if (app.manifest.description.toUpperCase().indexOf(token) !== -1) return true;
            }

            if (app.manifest.tags && app.manifest.tags.find(function (tag) { return tag.toUpperCase() === token; })) return true;

            if (!token) return true;

            // a hack to find out SSO enabled apps
            if (token === 'LDAP' && app.manifest.addons['ldap']) return true;
            if (token === 'OAUTH' && app.manifest.addons['oauth']) return true;
            if (token === 'SSO' && (app.manifest.addons['ldap'] || app.manifest.addons['oauth'])) return true;

            return false;
        });

        // only track after waiting 2seconds for the search string to settle
        if (searchTrackTimeout) clearTimeout(searchTrackTimeout);
        searchTrackTimeout = setTimeout(function () {
            _paq.push(['trackSiteSearch', $scope.searchString]);
        }, 2000);
    };

    $(window).on('hashchange', function () {
        $scope.category = window.location.hash.slice(1);
        $scope.searchString = '';

        $scope.$apply($scope.filterApps);
    });

    $scope.allApps = window.cloudronApps;
    $scope.filterApps();

    setTimeout(function () { $('[autofocus]').focus(); }, 0);
}]);

/* global $ */

$(document).ready(function () {
    'use strict';

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
});

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImluZGV4LmpzIiwidGhlbWUuanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQ2pHQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiJpbmRleC5qcyIsInNvdXJjZXNDb250ZW50IjpbIid1c2Ugc3RyaWN0JztcblxuLyogZ2xvYmFsIGFuZ3VsYXI6ZmFsc2UgKi9cbi8qIGV4cG9ydGVkIEFwcHN0b3JlQ29udHJvbGxlciAqL1xuXG4vLyBjcmVhdGUgbWFpbiBhcHBsaWNhdGlvbiBtb2R1bGVcbnZhciBhcHAgPSBhbmd1bGFyLm1vZHVsZSgnQXBwbGljYXRpb24nLCBbXSk7XG5cbmFwcC5jb250cm9sbGVyKCdBcHBzdG9yZUNvbnRyb2xsZXInLCBbJyRzY29wZScsIGZ1bmN0aW9uICgkc2NvcGUpIHtcbiAgICAkc2NvcGUucmVhZHkgPSBmYWxzZTtcbiAgICAkc2NvcGUuYWxsQXBwcyA9IFtdO1xuICAgICRzY29wZS5hcHBzID0gbnVsbDtcbiAgICAkc2NvcGUuYXBwID0gbnVsbDtcbiAgICAkc2NvcGUuc2VhcmNoU3RyaW5nID0gJyc7XG4gICAgJHNjb3BlLmNhY2hlZENhdGVnb3J5ID0gJyc7XG4gICAgJHNjb3BlLmNhdGVnb3J5ID0gd2luZG93LmxvY2F0aW9uLmhhc2guc2xpY2UoMSk7XG4gICAgJHNjb3BlLmNhdGVnb3J5TGlzdCA9IFtcbiAgICAgICAgeyB0YWc6ICdhbmFseXRpY3MnLCAgaWNvbjogJ2ZhcyBmYS1jaGFydC1iYXInLCAgICAgICBsYWJlbDogJ0FuYWx5dGljcycgfSxcbiAgICAgICAgeyB0YWc6ICdhdXRvbWF0aW9uJywgaWNvbjogJ2ZhcyBmYS1yb2JvdCcsICAgICAgICAgICBsYWJlbDogJ0F1dG9tYXRpb24nIH0sXG4gICAgICAgIHsgdGFnOiAnYmxvZycsICAgICAgIGljb246ICdmYXMgZmEtZm9udCcsICAgICAgICAgICAgbGFiZWw6ICdCbG9nJyB9LFxuICAgICAgICB7IHRhZzogJ2NoYXQnLCAgICAgICBpY29uOiAnZmFzIGZhLWNvbW1lbnRzJywgICAgICAgIGxhYmVsOiAnQ2hhdCcgfSxcbiAgICAgICAgeyB0YWc6ICdnaXQnLCAgICAgICAgaWNvbjogJ2ZhcyBmYS1jb2RlLWJyYW5jaCcsICAgICBsYWJlbDogJ0NvZGUgSG9zdGluZycgfSxcbiAgICAgICAgeyB0YWc6ICdDUk0nLCAgICAgICAgaWNvbjogJ2ZhYiBmYS1jb25uZWN0ZGV2ZWxvcCcsICBsYWJlbDogJ0NSTScgfSxcbiAgICAgICAgeyB0YWc6ICdkb2N1bWVudCcsICAgaWNvbjogJ2ZhcyBmYS1maWxlLXdvcmQnLCAgICAgICBsYWJlbDogJ0RvY3VtZW50cycgfSxcbiAgICAgICAgeyB0YWc6ICdlbWFpbCcsICAgICAgaWNvbjogJ2ZhcyBmYS1lbnZlbG9wZS1vcGVuJywgICBsYWJlbDogJ0VtYWlsJyB9LFxuICAgICAgICB7IHRhZzogJ2ZlZGVyYXRlZCcsICBpY29uOiAnZmFzIGZhLXByb2plY3QtZGlhZ3JhbScsIGxhYmVsOiAnRmVkZXJhdGVkJyB9LFxuICAgICAgICB7IHRhZzogJ3N5bmMnLCAgICAgICBpY29uOiAnZmFzIGZhLXN5bmMnLCAgICAgICAgICAgIGxhYmVsOiAnRmlsZSBTeW5jJyB9LFxuICAgICAgICB7IHRhZzogJ2ZpbmFuY2UnLCAgICBpY29uOiAnZmFzIGZhLWRvbGxhci1zaWduJywgICAgIGxhYmVsOiAnRmluYW5jZScgfSxcbiAgICAgICAgeyB0YWc6ICdmb3J1bScsICAgICAgaWNvbjogJ2ZhcyBmYS11c2VycycsICAgICAgICAgICBsYWJlbDogJ0ZvcnVtJyB9LFxuICAgICAgICB7IHRhZzogJ2dhbGxlcnknLCAgICBpY29uOiAnZmFzIGZhLWltYWdlcycsICAgICAgICAgIGxhYmVsOiAnR2FsbGVyeScgfSxcbiAgICAgICAgeyB0YWc6ICdnYW1lJywgICAgICAgaWNvbjogJ2ZhcyBmYS1nYW1lcGFkJywgICAgICAgICBsYWJlbDogJ0dhbWVzJyB9LFxuICAgICAgICB7IHRhZzogJ25vLWNvZGUnLCAgICBpY29uOiAnZmFzIGZhLWNvZGUnLCAgICAgICAgICAgIGxhYmVsOiAnTm8tY29kZScgfSxcbiAgICAgICAgeyB0YWc6ICdub3RlcycsICAgICAgaWNvbjogJ2ZhcyBmYS1zdGlja3ktbm90ZScsICAgICBsYWJlbDogJ05vdGVzJyB9LFxuICAgICAgICB7IHRhZzogJ3Byb2plY3QnLCAgICBpY29uOiAnZmFzIGZhLWNoYXJ0LWxpbmUnLCAgICAgIGxhYmVsOiAnUHJvamVjdCBNYW5hZ2VtZW50JyB9LFxuICAgICAgICB7IHRhZzogJ3ZvaXAnLCAgICAgICBpY29uOiAnZmFzIGZhLWhlYWRzZXQnLCAgICAgICAgIGxhYmVsOiAnVm9JUCcgfSxcbiAgICAgICAgeyB0YWc6ICd2cG4nLCAgICAgICAgaWNvbjogJ2ZhcyBmYS11c2VyLXNlY3JldCcsICAgICBsYWJlbDogJ1ZQTicgfSxcbiAgICAgICAgeyB0YWc6ICdob3N0aW5nJywgICAgaWNvbjogJ2ZhcyBmYS1iYXJzJywgICAgICAgICAgICBsYWJlbDogJ1dlYiBIb3N0aW5nJyB9LFxuICAgICAgICB7IHRhZzogJ3dpa2knLCAgICAgICBpY29uOiAnZmFiIGZhLXdpa2lwZWRpYS13JywgICAgIGxhYmVsOiAnV2lraScgfVxuICAgIF07XG5cbiAgICAkc2NvcGUuc2hvd0NhdGVnb3J5ID0gZnVuY3Rpb24gKGNhdGVnb3J5KSB7XG4gICAgICAgIGlmIChjYXRlZ29yeSkgd2luZG93LmxvY2F0aW9uLmhyZWYgPSAnIycgKyBjYXRlZ29yeTtcbiAgICAgICAgZWxzZSB3aW5kb3cubG9jYXRpb24uaHJlZiA9ICcjJztcblxuICAgICAgICB3aW5kb3cuc2Nyb2xsVG8oMCwwKTtcbiAgICB9O1xuXG4gICAgdmFyIHNlYXJjaFRyYWNrVGltZW91dCA9IG51bGw7XG5cbiAgICAkc2NvcGUuZmlsdGVyQXBwcyA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgJHNjb3BlLmFwcCA9IG51bGw7XG5cbiAgICAgICAgdmFyIHRva2VuID0gJHNjb3BlLnNlYXJjaFN0cmluZyA/ICRzY29wZS5zZWFyY2hTdHJpbmcudG9VcHBlckNhc2UoKSA6ICRzY29wZS5jYXRlZ29yeS50b1VwcGVyQ2FzZSgpO1xuXG4gICAgICAgIC8vIGNsZWFyIHRoZSBjYXRlZ29yeSBpZiB3ZSBzZWFyY2ggYWxsIGFwcHNcbiAgICAgICAgaWYgKCRzY29wZS5zZWFyY2hTdHJpbmcpICRzY29wZS5jYXRlZ29yeSA9ICcnO1xuXG4gICAgICAgICRzY29wZS5hcHBzID0gJHNjb3BlLmFsbEFwcHMuZmlsdGVyKGZ1bmN0aW9uIChhcHApIHtcbiAgICAgICAgICAgIC8vIG9ubHkgbG9vayBpbiBhbGwgY29udGVudCBpZiB3ZSBzZWFyY2gsIG5vdCBjYXRlZ29yeVxuICAgICAgICAgICAgaWYgKCRzY29wZS5zZWFyY2hTdHJpbmcpIHtcbiAgICAgICAgICAgICAgICBpZiAoYXBwLm1hbmlmZXN0LmlkLnRvVXBwZXJDYXNlKCkuaW5kZXhPZih0b2tlbikgIT09IC0xKSByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgICAgICBpZiAoYXBwLm1hbmlmZXN0LnRpdGxlLnRvVXBwZXJDYXNlKCkuaW5kZXhPZih0b2tlbikgIT09IC0xKSByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgICAgICBpZiAoYXBwLm1hbmlmZXN0LnRhZ2xpbmUudG9VcHBlckNhc2UoKS5pbmRleE9mKHRva2VuKSAhPT0gLTEpIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgICAgIGlmIChhcHAubWFuaWZlc3QuZGVzY3JpcHRpb24udG9VcHBlckNhc2UoKS5pbmRleE9mKHRva2VuKSAhPT0gLTEpIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAoYXBwLm1hbmlmZXN0LnRhZ3MgJiYgYXBwLm1hbmlmZXN0LnRhZ3MuZmluZChmdW5jdGlvbiAodGFnKSB7IHJldHVybiB0YWcudG9VcHBlckNhc2UoKSA9PT0gdG9rZW47IH0pKSByZXR1cm4gdHJ1ZTtcblxuICAgICAgICAgICAgaWYgKCF0b2tlbikgcmV0dXJuIHRydWU7XG5cbiAgICAgICAgICAgIC8vIGEgaGFjayB0byBmaW5kIG91dCBTU08gZW5hYmxlZCBhcHBzXG4gICAgICAgICAgICBpZiAodG9rZW4gPT09ICdMREFQJyAmJiBhcHAubWFuaWZlc3QuYWRkb25zWydsZGFwJ10pIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgaWYgKHRva2VuID09PSAnT0FVVEgnICYmIGFwcC5tYW5pZmVzdC5hZGRvbnNbJ29hdXRoJ10pIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgaWYgKHRva2VuID09PSAnU1NPJyAmJiAoYXBwLm1hbmlmZXN0LmFkZG9uc1snbGRhcCddIHx8IGFwcC5tYW5pZmVzdC5hZGRvbnNbJ29hdXRoJ10pKSByZXR1cm4gdHJ1ZTtcblxuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9KTtcblxuICAgICAgICAvLyBvbmx5IHRyYWNrIGFmdGVyIHdhaXRpbmcgMnNlY29uZHMgZm9yIHRoZSBzZWFyY2ggc3RyaW5nIHRvIHNldHRsZVxuICAgICAgICBpZiAoc2VhcmNoVHJhY2tUaW1lb3V0KSBjbGVhclRpbWVvdXQoc2VhcmNoVHJhY2tUaW1lb3V0KTtcbiAgICAgICAgc2VhcmNoVHJhY2tUaW1lb3V0ID0gc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBfcGFxLnB1c2goWyd0cmFja1NpdGVTZWFyY2gnLCAkc2NvcGUuc2VhcmNoU3RyaW5nXSk7XG4gICAgICAgIH0sIDIwMDApO1xuICAgIH07XG5cbiAgICAkKHdpbmRvdykub24oJ2hhc2hjaGFuZ2UnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICRzY29wZS5jYXRlZ29yeSA9IHdpbmRvdy5sb2NhdGlvbi5oYXNoLnNsaWNlKDEpO1xuICAgICAgICAkc2NvcGUuc2VhcmNoU3RyaW5nID0gJyc7XG5cbiAgICAgICAgJHNjb3BlLiRhcHBseSgkc2NvcGUuZmlsdGVyQXBwcyk7XG4gICAgfSk7XG5cbiAgICAkc2NvcGUuYWxsQXBwcyA9IHdpbmRvdy5jbG91ZHJvbkFwcHM7XG4gICAgJHNjb3BlLmZpbHRlckFwcHMoKTtcblxuICAgIHNldFRpbWVvdXQoZnVuY3Rpb24gKCkgeyAkKCdbYXV0b2ZvY3VzXScpLmZvY3VzKCk7IH0sIDApO1xufV0pO1xuIiwiLyogZ2xvYmFsICQgKi9cblxuJChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24gKCkge1xuICAgICd1c2Ugc3RyaWN0JztcblxuICAgICQoZnVuY3Rpb24gKCkge1xuICAgICAgICAkKCdbZGF0YS10b2dnbGU9XCJ0b29sdGlwXCJdJykudG9vbHRpcCgpO1xuICAgIH0pO1xufSk7XG4iXX0=
