{+START,IF,{$NEQ,{I},0}}{+START,IF_NON_EMPTY,{URL}}<a rel="lightbox" href="{URL*}">{+END}{+END}<img{+START,IF_NON_EMPTY,{WIDTH}} width="{WIDTH*}"{+END}{+START,IF_NON_EMPTY,{HEIGHT}} height="{HEIGHT*}"{+END} src="{THUMB_URL*}" alt="{!IMAGE}" />{+START,IF,{$NEQ,{I},0}}{+START,IF_NON_EMPTY,{URL}}</a>{+END}{+END}

