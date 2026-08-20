$(document).ready(function () {
    //set custom scrollbar
    setPageScrollable();
    setMenuScrollable();
    $(window).resize(function () {
        setPageScrollable();
        setMenuScrollable();
    });

    //expand or collaps sidebar menu items
    $("#sidebar-menu a").on("click", function () {
        var $target = $(this).parent();
        if ($target.hasClass('main')) {
            $("#sidebar-menu li").removeClass("active");
            $target.addClass("active");
        }
    });

    //the top offset will be changed while scrolling
    //so, we've to store it first
    var sectionsData = [];
    setTimeout(function () {
        $('section').each(function () {
            sectionsData.push({
                "name": "#" + $(this).attr("id"),
                "top": $(this).offset().top,
                "height": $(this).height()
            });
        });
    }, 500);

    //add active class on scrolling window
    $("#scrollable-page").scroll(function () {
        var scrollPosition = $("#scrollable-page").scrollTop() + 70;

        for (i = 0; i < sectionsData.length; i++) {
            var section = sectionsData[i];

            if ((section.top <= scrollPosition) && ((section.top + section.height) >= scrollPosition)) {
                $("#sidebar-menu li").removeClass('active open');

                var $parentList = $("#sidebar-menu").find("a[href='" + section.name + "']").parent("li");
                if ($parentList.hasClass("main")) { //main list
                    $parentList.addClass("active");
                }
            }
        }

    });

    //expand nested list from hash link
    var target = window.location.hash;

    if (target) {
        var $selector = $("#sidebar-menu").find("a[href='" + target + "']");

        if (!($selector).hasClass("main")) {
            $selector.closest("li.main").addClass("active");
        }
    }
});

setPageScrollable = function () {
    if ($(window).width() <= 640) {
        initScrollbar('.scrollable', {
            setHeight: $(window).height() - 45
        });
    } else {
        initScrollbar('.scrollable', {
            setHeight: $(window).height() - 54
        });
    }

};

initScrollbar = function (selector, options) {
    if (!options) {
        options = {};
    }
    if (!$(selector).length) {
        return false;
    }

    var defaults = {wheelPropagation: true},
            settings = $.extend({}, defaults, options);

    if (options.setHeight) {
        $(selector).css({"height": settings.setHeight + "px", position: "relative"});
    }

    $(selector).css({"overflow-y": "scroll"});
};

//set scrollbar on left menu
setMenuScrollable = function () {
    if ($("#sidebar-scroll").height() > ($(window).height() - 65)) {
        initScrollbar('#sidebar-scroll', {
            setHeight: $(window).height() - 45
        });
    }
};
