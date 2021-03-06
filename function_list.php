<?php
     /*
     Copyright (C)

     This program is free software; you can redistribute it and/or modify it
     under the terms of the GNU General Public License as published by the Free
     Software Foundation; either version 2 of the License, or (at your option)
     any later version.

     This program is distributed in the hope that it will be useful, but WITHOUT
     ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
     FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
     more details.

     You should have received a copy of the GNU General Public License along
     with this program; if not, write to the Free Software Foundation, Inc.,
     59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
     */
?>
<?php
	include('inc/header.php');

	mssql_select_db($_SESSION['database']);
?>

<form name="form1" method="post" action="function_list.php">
<table width="350" cellpadding="3" cellspacing="3" style="border: 1px solid">
	<tr>
		<td align="center" colspan="5" style="background: #D0DCE0">
			<b>Functions</b>
		</td>
	</tr>
	<tr>
		<td style="background: #D0DCE0">&nbsp;</td>
		<td align="center" style="background: #D0DCE0">
			<b>Name</b>
		</td>
		<td align="center" colspan="3" style="background: #D0DCE0">
			<b>Action</b>
		</td>
	</tr>
	<?php
		$toggle = true;
		$colors = array('#DDDDDD','#CCCCCC');

		$proc_query = @mssql_query('sp_help') or die(throwSQLError('unable to retrieve list of stored procedures'));
		while($row = mssql_fetch_assoc($proc_query))
		{
			if(substr_count($row['Object_type'],'function') > 0 && ($row['Owner'] == 'dbo' || $_SETTINGS['showsysdata']))
			{
				if($toggle)
					$bg = $colors[0];
				else
					$bg = $colors[1];

				$toggle = !$toggle;

				echo '<tr>';
				echo('<td align="center" style="background: ' . $bg . '" nowrap><input type="checkbox" name="functions[]" value="' . $row['Name'] . '"></td>');
				echo('<td style="background: ' . $bg . '" nowrap>' . $row['Name'] . '</td>');
				echo('<td align="center" style="background: ' . $bg . '" nowrap><a href="function_modify.php?function=' . urlencode($row['Name']) . '">Modify</a></td>');
				echo('<td align="center" style="background: ' . $bg . '" nowrap><a href="function_drop.php?function=' . urlencode($row['Name']) . '&returnto=function_list.php">Drop</a></td>');
				echo '</tr>';

				unset($row);
			}
		}
	?>
</table>
</form>

<?php include('inc/footer.php'); ?>