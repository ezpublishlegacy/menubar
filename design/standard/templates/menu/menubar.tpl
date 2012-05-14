{* Menubar Template *}
{if $menubar.item_count}
{if $menubar.has_header}
	<h2{if $menubar.header.class} class="{$menubar.header.class}"{/if}>{if $menubar.header.link}<a href="{$menubar.header.link}">{/if}{$menubar.header.content}{if $menubar.header.link}</a>{/if}</h2>
{/if}
<ul class="menu {$menubar.class}">
	{foreach $menubar.items as $key=>$item}
{if and($menubar.item_limit, gt($key, 0), eq($key|mod($menubar.item_limit), 0))}
</ul>
<ul class="menu {$menubar.class}">
{/if}
	<li{if $item.class} class="{$item.class}"{/if}>
		{if $item.link}<a href="{$item.link}"{if $item.is_external} class="external" rel="external" target="_blank"{/if}>{else}<span class="inactive">{/if}{$item.content}{if $item.link}</a>{else}</span>{/if}{if $item.delimiter}<span class="delimiter">{$item.delimiter}</span>{/if}
		{if $item.menu}{$item.menu|menubar()}{/if}
	</li>
	{/foreach}
</ul>
{/if}
