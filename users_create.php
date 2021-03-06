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

if(!empty($_POST['username']))
{
	@mssql_query('sp_addlogin ' . $_POST['username'] . ',' . $_POST['password'] . ',' . urldecode($_POST['defdb']) . ';') or die(throwSQLError('unable to create user'));

	if(is_array($_POST['permissions']))
		foreach($_POST['permissions'] AS $row)
			@mssql_query('sp_addsrvrolemember ' . $_POST['username'] . ',' . $row . ';') or die(throwSQLError('unable to grant permissions'));

	@mssql_select_db(urldecode($_POST['defdb'])) or die(throwSQLError('unable to select database'));

	@mssql_query('sp_adduser ' . $_POST['username'] . ';') or die(throwSQLError('unable to add user'));

	if(is_array($_POST['dbpermissions']))
		foreach($_POST['dbpermissions'] AS $row)
			@mssql_query('sp_addrolemember db_' . $row . ', ' . $_POST['username'] . ';') or die(throwSQLError('unable to grant permissions'));

	$operations = array('insert','update','select','delete');
	foreach($operations AS $oper)
		if(is_array($_POST[$oper]))
			foreach($_POST[$oper] AS $row)
				@mssql_query('GRANT ' . strtoupper($oper) .' ON ' . $row . ' TO ' . $_POST['username'] . ';') or die(throwSQLError('unable to grant permissions'));

	echo '<meta http-equiv="refresh" content="0;url=users_list.php">';
	include('inc/footer.php');
}

$db_info_query = @mssql_query('sp_helpdb;') or die(throwSQLError('unable to retrieve databases'));

$dbinfo = array();

while($row = mssql_fetch_array($db_info_query))
	if(!in_array($row['name'],$_SETTINGS['dbexclude']))
		$dbinfo[] = $row['name'];

if($_GET['dbname'] == '')
	$_GET['dbname'] = $dbinfo[0];
else
	$_GET['dbname'] = urldecode($_GET['dbname']);

mssql_select_db($_GET['dbname']);
$table_query = @mssql_query('sp_tables;') or die(throwSQLError('unable to retrieve list of tables'));

$tableinfo = array();

while($row = mssql_fetch_array($table_query))
{
	if($row['TABLE_TYPE'] == 'TABLE' && $row['TABLE_NAME'] != 'dtproperties')
	{
		if($row['TABLE_OWNER'] != 'dbo')
			$row['TABLE_NAME'] = ($row['TABLE_OWNER'] . '.' . $row['TABLE_NAME']);

		$tableinfo[] = $row['TABLE_NAME'];
	}
}

if(!empty($_GET['newusername']))
	$_POST['newusername'] = $_GET['newusername'];

?>

<script language="javascript">
function doCheck(col,mode)
{
	for(counter = 0; counter < document.form1.rowcount.value; counter++)
		document.forms['form1'].elements[col+'[]'][counter].checked = mode;
}
</script>

<form name="form1" method="post" action="users_create.php">
<?php echo('<input type="hidden" name="rowcount" value="' . count($tableinfo) . '">'); ?>
<table width="300" cellpadding="3" cellspacing="3" style="border: 1px solid">
	<tr>
		<td align="center" colspan="4" style="background: #D0DCE0">
			<b>Create User</b>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2" style="background: #D0DCE0">
			<b>General:</b>
		</td>
		<td align="center" colspan="2" style="background: #D0DCE0">
			<b>Server Role(s):</b>
		</td>
	</tr>
	<tr>
		<td align="right" nowrap>
			<b>Username:</b>
		</td>
		<td nowrap>
			<input name="username" size="15" maxlength="32" value="<?php echo($_POST['newusername']); ?>">
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="sysadmin"> System Admin
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="securityadmin"> Security Admin
		</td>
	</tr>
	<tr>
		<td align="right" nowrap>
			<b>Password:</b>
		</td>
		<td nowrap>
			<input type="password" name="password" size="15" maxlength="32">
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="serveradmin"> Server Admin
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="setupadmin"> Setup Admin
		</td>
	</tr>
	<tr>
		<td align="right" nowrap>
			<b>Database:</b>
		</td>
		<td nowrap>
			<select name="defdb" onchange="javascript:location='users_create.php?newusername=' + document.form1.username.value + '&dbname='+document.form1.defdb.value;">
				<?php
					foreach($dbinfo AS $row)
					{
						if($row != $_GET['dbname'])
							echo('<option value="' . urlencode($row) . '">' . $row . '</option>');
						else
							echo('<option value="' . urlencode($row) . '" selected>' . $row . '</option>');
					}
				?>
			</select>
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="processadmin"> Process Admin
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="diskadmin"> Disk Admin
		</td>
	</tr>
	<tr>
		<td colspan="2">
			&nbsp;
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="dbcreator"> DB Creator
		</td>
		<td nowrap>
			<input type="checkbox" name="permissions[]" value="bulkadmin"> Bulk Admin
		</td>
	</tr>
	<tr>
		<td align="center" colspan="4" style="background: #D0DCE0">
			<b>Database Permissions:</b>
		</td>
	</tr>
	<tr>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="accessadmin"> Access Admin
		</td>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="datareader"> Data Reader
		</td>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="datawriter"> Data Writer
		</td>
		<td>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="owner"> DB Owner
		</td>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="denydatareader"> Deny Data Reader
		</td>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="denydatawriter"> Deny Data Writer
		</td>
		<td>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="backupoperator"> Backup Operator
		</td>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="ddladmin"> DDL Admin
		</td>
		<td nowrap>
			<input type="checkbox" name="dbpermissions[]" value="securityadmin"> Security Admin
		</td>
		<td>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td colspan="4">
			<table width="100%" cellpadding="3" cellspacing="3">
				<tr>
					<td align="center" colspan="5" style="background: #D0DCE0">
						<b>Table Permissions:</b>
					</td>
				</tr>
				<tr>
					<td align="center">
						<b>Table Name:</b>
					</td>
					<td align="center">
						<font color="red"><b>INSERT</b></font>
					</td>
					<td align="center">
						<font color="red"><b>UPDATE</b></font>
					</td>
					<td align="center">
						<font color="red"><b>SELECT</b></font>
					</td>
					<td align="center">
						<font color="red"><b>DELETE</b></font>
					</td>
				</tr>
				<?php
					$colors = array('#DDDDDD','#CCCCCC');
					$toggle = true;

					foreach($tableinfo AS $row)
					{
						if($toggle)
							$bgcolor = $colors[0];
						else
							$bgcolor = $colors[1];

						$toggle = !$toggle;

						echo '<tr>';
						echo('<td style="background:' . $bgcolor . '">' . $row . '</td>');
						echo('<td align="center" style="background:' . $bgcolor . '"><input type="checkbox" name="insert[]" value="' . $row . '"></td>');
						echo('<td align="center" style="background:' . $bgcolor . '"><input type="checkbox" name="update[]" value="' . $row . '"></td>');
						echo('<td align="center" style="background:' . $bgcolor . '"><input type="checkbox" name="select[]" value="' . $row . '"></td>');
						echo('<td align="center" style="background:' . $bgcolor . '"><input type="checkbox" name="delete[]" value="' . $row . '"></td>');
						echo '</tr>';
					}
				?>
				<tr>
					<td align="right">
						<b>Check:</b>
					</td>
					<td align="center">
						<font size="-2">(<a href="javascript:doCheck('insert',true);">All</a> / <a href="javascript:doCheck('insert',false);">None</a>)</font>
					</td>
					<td align="center">
						<font size="-2">(<a href="javascript:doCheck('update',true);">All</a> / <a href="javascript:doCheck('update',false);">None</a>)</font>
					</td>
					<td align="center">
						<font size="-2">(<a href="javascript:doCheck('select',true);">All</a> / <a href="javascript:doCheck('select',false);">None</a>)</font>
					</td>
					<td align="center">
						<font size="-2">(<a href="javascript:doCheck('delete',true);">All</a> / <a href="javascript:doCheck('delete',false);">None</a>)</font>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="4" style="background: #D0DCE0">
			<input type="button" value="Create" onclick="javascript: if(document.form1.password.value == '') alert('A password is required for all accounts.'); else document.form1.submit();">
		</td>
	</tr>
</table>
</form>

<script language="javascript">
document.form1.username.focus();
</script>

<?php include('inc/footer.php'); ?>
