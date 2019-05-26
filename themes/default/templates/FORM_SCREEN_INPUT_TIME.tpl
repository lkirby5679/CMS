{$REQUIRE_JAVASCRIPT,modernizr}
{$REQUIRE_JAVASCRIPT,jquery}
{$REQUIRE_JAVASCRIPT,jquery_ui}
{$REQUIRE_JAVASCRIPT,widget_date}
{$REQUIRE_CSS,jquery_ui}
{$REQUIRE_CSS,widget_date}

{$SET,time_value,{$PAD_LEFT,{HOUR},2,0}:{$PAD_LEFT,{MINUTE},2,0}}
<input name="{NAME*}" id="{NAME*}" type="time" size="5"{+START,IF_PASSED,TABINDEX} tabindex="{TABINDEX*}"{+END} value="{+START,IF_NON_EMPTY,{HOUR}}{$GET*,time_value}{+END}" />

<script>// <![CDATA[
	add_event_listener_abstract(window,'load',function() {
		if (!Modernizr.inputtypes.time) $('#{NAME;/}').inputTime({});
	});
//]]></script>
